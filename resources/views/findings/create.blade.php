@extends('layouts.app')
@section('title', 'Dodaj znalezisko')

@push('styles')
<style>
    #map-picker {
        height: 260px;
        border-radius: 1rem;
        overflow: hidden;
        border: 2px solid #404060;
        transition: border-color 0.2s;
    }
    #map-picker.selected { border-color: #f59e0b; }
    .leaflet-marker-icon { filter: hue-rotate(200deg) brightness(1.3); }
    .locate-btn {
        position: absolute;
        bottom: 12px; right: 12px;
        z-index: 1000;
        background: #2a2a3e;
        border: 1px solid #404060;
        color: #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.5rem 0.875rem;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex; align-items: center; gap: 0.4rem;
        cursor: pointer;
        touch-action: manipulation;
    }
    .locate-btn:active { opacity: 0.7; }
    .screen-scroll { overflow-y: auto; flex: 1; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">
            ‹
        </a>
        <h1 class="text-lg font-bold text-white">Nowe znalezisko</h1>
    </div>

    {{-- Scrollable form --}}
    <div class="screen-scroll px-5 py-5">
        <form
            method="POST"
            action="{{ route('findings.store') }}"
            enctype="multipart/form-data"
            id="findingForm"
            class="flex flex-col gap-5"
        >
            @csrf

            {{-- Map picker --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">
                    📍 Lokalizacja znaleziska
                    <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <div id="map-picker"></div>
                    <button type="button" class="locate-btn" id="locateBtn">
                        🎯 Moja pozycja
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1.5 ml-1" id="coordsLabel">
                    Dotknij mapę, aby zaznaczyć miejsce znalezienia
                </p>
                <input type="hidden" name="latitude" id="lat" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="lng" value="{{ old('longitude') }}">
                <input type="hidden" name="city" id="city" value="{{ old('city') }}">
                <input type="hidden" name="city_lat" id="city_lat" value="{{ old('city_lat') }}">
                <input type="hidden" name="city_lng" id="city_lng" value="{{ old('city_lng') }}">
                <input type="hidden" name="voivodeship" id="voivodeship" value="{{ old('voivodeship') }}">
                <input type="hidden" name="county" id="county" value="{{ old('county') }}">
                <p class="text-xs text-gray-500 mt-1 ml-1 hidden" id="cityLabel"></p>
                @error('latitude')
                    <p class="text-red-400 text-sm mt-1">Zaznacz lokalizację na mapie.</p>
                @enderror
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🪙 Nazwa znaleziska <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="np. Moneta srebrna, Fibula, Pierścionek..."
                    class="input-field @error('name') border-red-500 @enderror"
                >
                @error('name')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Depth --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📏 Głębokość wykopu <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <input
                        type="number"
                        name="depth_cm"
                        value="{{ old('depth_cm') }}"
                        placeholder="0"
                        min="0"
                        max="9999"
                        inputmode="numeric"
                        class="input-field @error('depth_cm') border-red-500 @enderror pr-14"
                        style="padding-right: 3.5rem"
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">cm</span>
                </div>
                @error('depth_cm')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📝 Opis <span class="text-gray-500 font-normal">(opcjonalny)</span>
                </label>
                <textarea
                    name="description"
                    placeholder="Opisz znalezisko, stan zachowania, kontekst odkrycia..."
                    rows="3"
                    class="input-field resize-none"
                >{{ old('description') }}</textarea>
            </div>

            {{-- Photo --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📷 Zdjęcie <span class="text-gray-500 font-normal">(opcjonalne)</span>
                </label>
                <div id="photoPickerArea">
                    <label class="flex flex-col items-center justify-center gap-2 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors py-6" id="photoLabel">
                        <span class="text-3xl">📷</span>
                        <span class="text-sm text-gray-400">Dotknij, aby dodać zdjęcie</span>
                        <input type="file" name="photo" accept="image/*" capture="environment" class="hidden" id="photoInput">
                    </label>
                </div>
                <div id="photoPreviewArea" class="hidden relative rounded-xl overflow-hidden border-2 border-amber-500">
                    <img id="photoPreview" src="" alt="Podgląd zdjęcia" class="w-full object-cover max-h-64">
                    <button type="button" id="removePhotoBtn"
                        class="absolute top-2 right-2 bg-black/60 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg leading-none">
                        ×
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-primary" id="submitBtn">
                Dodaj znalezisko
            </button>

        </form>

        <div class="h-8"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Init map centered on Poland
    const initialLat = {{ old('latitude', 52.0) }};
    const initialLng = {{ old('longitude', 19.0) }};
    const initialZoom = {{ old('latitude') ? 14 : 6 }};

    const map = L.map('map-picker', {
        center: [initialLat, initialLng],
        zoom: initialZoom,
        zoomControl: true,
        attributionControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    let marker = null;

    @if(old('latitude') && old('longitude'))
        placeMarker({{ old('latitude') }}, {{ old('longitude') }});
    @endif
    @if(old('city'))
        const _oldCity = @json(old('city'));
        document.getElementById('cityLabel').textContent = '🏘️ ' + _oldCity;
        document.getElementById('cityLabel').classList.remove('hidden');
    @endif

    function placeMarker(lat, lng) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], {
            icon: L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41],
            })
        }).addTo(map);
        document.getElementById('lat').value = lat.toFixed(7);
        document.getElementById('lng').value = lng.toFixed(7);
        document.getElementById('coordsLabel').textContent =
            `📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        document.getElementById('map-picker').classList.add('selected');
        reverseGeocode(lat, lng);
    }

    function reverseGeocode(lat, lng) {
        const cityLabel = document.getElementById('cityLabel');
        const cityInput        = document.getElementById('city');
        const cityLatInput     = document.getElementById('city_lat');
        const cityLngInput     = document.getElementById('city_lng');
        const voivodeshipInput = document.getElementById('voivodeship');
        const countyInput      = document.getElementById('county');

        cityLabel.textContent = '🔍 Wykrywanie miejscowości...';
        cityLabel.classList.remove('hidden');
        cityInput.value = '';
        cityLatInput.value = '';
        cityLngInput.value = '';
        voivodeshipInput.value = '';
        countyInput.value = '';

        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=pl`, {
            headers: { 'Accept-Language': 'pl' }
        })
        .then(r => r.json())
        .then(data => {
            const a = data.address ?? {};
            const city        = a.city ?? a.town ?? a.village ?? a.hamlet ?? a.suburb ?? '';
            const voivodeship = a.state ?? '';
            const county      = a.county ?? a.municipality ?? '';

            cityInput.value        = city;
            cityLatInput.value     = parseFloat(data.lat).toFixed(7);
            cityLngInput.value     = parseFloat(data.lon).toFixed(7);
            voivodeshipInput.value = voivodeship;
            countyInput.value      = county;

            if (city) {
                cityLabel.textContent = `🏘️ ${city}${voivodeship ? ', ' + voivodeship : ''}`;
            } else {
                cityLabel.textContent = '❓ Nie udało się wykryć miejscowości';
            }
        })
        .catch(() => {
            cityLabel.textContent = '❓ Nie udało się wykryć miejscowości';
        });
    }

    map.on('click', e => placeMarker(e.latlng.lat, e.latlng.lng));

    // Locate me
    document.getElementById('locateBtn').addEventListener('click', () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 15);
            placeMarker(latitude, longitude);
        }, () => {
            alert('Nie udało się pobrać lokalizacji.');
        });
    });

    // Photo preview
    const photoInput = document.getElementById('photoInput');
    const photoPickerArea = document.getElementById('photoPickerArea');
    const photoPreviewArea = document.getElementById('photoPreviewArea');
    const photoPreview = document.getElementById('photoPreview');

    photoInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                photoPreview.src = e.target.result;
                photoPickerArea.classList.add('hidden');
                photoPreviewArea.classList.remove('hidden');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    document.getElementById('removePhotoBtn').addEventListener('click', function () {
        photoInput.value = '';
        photoPreview.src = '';
        photoPreviewArea.classList.add('hidden');
        photoPickerArea.classList.remove('hidden');
    });

    // Form validation
    document.getElementById('findingForm').addEventListener('submit', function (e) {
        if (!document.getElementById('lat').value) {
            e.preventDefault();
            alert('Zaznacz lokalizację na mapie!');
            document.getElementById('map-picker').scrollIntoView({ behavior: 'smooth' });
        }
    });
</script>
@endpush
