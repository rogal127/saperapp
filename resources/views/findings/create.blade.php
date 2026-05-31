@extends('layouts.app')
@section('title', 'Dodaj znalezisko')

@push('styles')
<style>
    #map-picker {
        border-radius: 0;
        overflow: hidden;
    }
    #map-picker.selected .leaflet-container { cursor: crosshair; }
    .leaflet-marker-icon { filter: hue-rotate(200deg) brightness(1.3); }
    .locate-btn {
        position: absolute;
        bottom: 16px; right: 12px;
        z-index: 1000;
        background: #2a2a3e;
        border: 1px solid #404060;
        color: #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex; align-items: center; gap: 0.4rem;
        cursor: pointer;
        touch-action: manipulation;
        box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }
    .locate-btn:active { opacity: 0.7; }
    .step-screen { display: none; flex-direction: column; height: 100%; }
    .step-screen.active { display: flex; }
    .screen-scroll { overflow-y: auto; flex: 1; }
    .step-indicator span {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #404060;
        transition: background 0.2s;
    }
    .step-indicator span.active { background: #f59e0b; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- === STEP 1: Map picker === --}}
    <div class="step-screen active" id="step1">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">
                ‹
            </a>
            <div class="flex-1">
                <h1 class="text-lg font-bold text-white">Lokalizacja znaleziska</h1>
                <p class="text-xs text-gray-500">Krok 1 z 2</p>
            </div>
            <div class="step-indicator flex gap-1.5">
                <span class="active"></span>
                <span></span>
            </div>
        </div>

        {{-- Map (fills remaining space) --}}
        <div class="relative flex-1">
            <div id="map-picker" class="w-full h-full"></div>
            <button type="button" class="locate-btn" id="locateBtn">
                🎯 Moja pozycja
            </button>
        </div>

        {{-- Bottom bar --}}
        <div class="flex-shrink-0 px-5 py-4 border-t border-surface-card bg-surface">
            <p class="text-xs text-gray-500 mb-3 text-center" id="coordsLabel">
                Dotknij mapę, aby zaznaczyć miejsce znalezienia
            </p>
            <p class="text-xs text-gray-500 text-center hidden mb-3" id="cityLabel"></p>
            <button type="button" id="nextBtn" class="btn-primary opacity-40" disabled>
                Dalej — uzupełnij szczegóły →
            </button>
        </div>

    </div>

    {{-- === STEP 2: Details === --}}
    <div class="step-screen" id="step2">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <button type="button" id="backBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">
                ‹
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-bold text-white">Szczegóły znaleziska</h1>
                <p class="text-xs text-gray-500">Krok 2 z 2</p>
            </div>
            <div class="step-indicator flex gap-1.5">
                <span></span>
                <span class="active"></span>
            </div>
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

                {{-- Hidden location fields --}}
                <input type="hidden" name="latitude" id="lat" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="lng" value="{{ old('longitude') }}">
                <input type="hidden" name="city" id="city" value="{{ old('city') }}">
                <input type="hidden" name="city_lat" id="city_lat" value="{{ old('city_lat') }}">
                <input type="hidden" name="city_lng" id="city_lng" value="{{ old('city_lng') }}">
                <input type="hidden" name="voivodeship" id="voivodeship" value="{{ old('voivodeship') }}">
                <input type="hidden" name="county" id="county" value="{{ old('county') }}">

                {{-- Location summary --}}
                <div class="card flex items-center gap-3">
                    <span class="text-2xl">📍</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-500 mb-0.5">Lokalizacja</p>
                        <p class="text-sm font-semibold text-white truncate" id="locationSummary">—</p>
                    </div>
                    <button type="button" id="changeLocationBtn" class="text-amber-400 text-xs font-semibold whitespace-nowrap">
                        Zmień
                    </button>
                </div>

                @error('latitude')
                    <p class="text-red-400 text-sm -mt-3">Zaznacz lokalizację na mapie.</p>
                @enderror

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
                            class="input-field @error('depth_cm') border-red-500 @enderror"
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
                            <input type="file" name="photo" accept="image/*" class="hidden" id="photoInput">
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

</div>
@endsection

@push('scripts')
<script>
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
    let locationReady = false;

    @if(old('latitude') && old('longitude'))
        placeMarker({{ old('latitude') }}, {{ old('longitude') }});
    @endif
    @if(old('city'))
        const _oldCity = @json(old('city'));
        document.getElementById('cityLabel').textContent = '🏘️ ' + _oldCity;
        document.getElementById('cityLabel').classList.remove('hidden');
        document.getElementById('locationSummary').textContent = _oldCity;
        // On validation error, go straight to step 2
        showStep(2);
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
        document.getElementById('coordsLabel').textContent = `📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        locationReady = true;
        document.getElementById('nextBtn').removeAttribute('disabled');
        document.getElementById('nextBtn').classList.remove('opacity-40');
        reverseGeocode(lat, lng);
    }

    function reverseGeocode(lat, lng) {
        const cityLabel        = document.getElementById('cityLabel');
        const cityInput        = document.getElementById('city');
        const cityLatInput     = document.getElementById('city_lat');
        const cityLngInput     = document.getElementById('city_lng');
        const voivodeshipInput = document.getElementById('voivodeship');
        const countyInput      = document.getElementById('county');
        const locationSummary  = document.getElementById('locationSummary');

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

            const label = city ? `🏘️ ${city}${voivodeship ? ', ' + voivodeship : ''}` : '❓ Nie udało się wykryć miejscowości';
            cityLabel.textContent = label;
            locationSummary.textContent = city
                ? `${city}${voivodeship ? ', ' + voivodeship : ''} (${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)})`
                : `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
        })
        .catch(() => {
            cityLabel.textContent = '❓ Nie udało się wykryć miejscowości';
            locationSummary.textContent = `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
        });
    }

    map.on('click', e => placeMarker(e.latlng.lat, e.latlng.lng));

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

    // Step navigation
    function showStep(n) {
        document.getElementById('step1').classList.toggle('active', n === 1);
        document.getElementById('step2').classList.toggle('active', n === 2);
        if (n === 1) {
            // Trigger map resize after step becomes visible
            setTimeout(() => map.invalidateSize(), 50);
        }
    }

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (!locationReady) return;
        showStep(2);
    });

    document.getElementById('backBtn').addEventListener('click', () => showStep(1));
    document.getElementById('changeLocationBtn').addEventListener('click', () => showStep(1));

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
            showStep(1);
            alert('Zaznacz lokalizację na mapie!');
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Dodawanie...';
        btn.classList.add('opacity-60');
    });
</script>
@endpush
