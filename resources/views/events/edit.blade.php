@extends('layouts.app')
@section('title', 'Edytuj imprezę')

@push('styles')
<style>
    #map-picker { height: 16rem; border-radius: 1rem; overflow: hidden; }
    #map-picker .leaflet-container { cursor: crosshair; }
    .leaflet-container { background: #1a1a2e; }
</style>
@endpush

@php
    $isAdmin = (bool) session('api_user.is_admin');
@endphp

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('events.show', $event['id']) }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white">Edytuj imprezę</h1>
            @unless($isAdmin)
            <p class="text-xs text-gray-500">Po zapisaniu zmian impreza ponownie trafi do akceptacji</p>
            @endunless
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-5">
        <form id="eventForm" action="{{ route('events.update', $event['id']) }}" class="flex flex-col gap-5">
            @csrf
            @method('PUT')

            {{-- Photo --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📷 Zdjęcie</label>
                <label id="photoTile" class="flex flex-col items-center justify-center gap-1 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors h-40 overflow-hidden relative">
                    <img id="photoPreview" src="{{ $event['photo_url'] ?? '' }}" class="{{ empty($event['photo_url']) ? 'hidden' : '' }} absolute inset-0 w-full h-full object-cover" alt="">
                    <span id="photoPlaceholder" class="{{ empty($event['photo_url']) ? '' : 'hidden' }} flex flex-col items-center gap-1">
                        <span class="text-3xl">📷</span>
                        <span class="text-xs text-gray-400">Dotknij, aby dodać plakat lub zdjęcie</span>
                    </span>
                    <input type="file" name="photo" accept="image/*" class="hidden" id="photoInput">
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-1">Dotknij zdjęcia, aby je podmienić. Pozostaw bez zmian, żeby zachować obecne.</p>
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🎉 Nazwa <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ $event['name'] ?? '' }}" placeholder="np. Zlot poszukiwaczy Mazowsza" class="input-field">
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Od <span class="text-red-400">*</span></label>
                    <input type="date" name="starts_at" value="{{ $event['starts_at'] ?? '' }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Do <span class="text-red-400">*</span></label>
                    <input type="date" name="ends_at" value="{{ $event['ends_at'] ?? '' }}" class="input-field">
                </div>
            </div>

            {{-- Voivodeship --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🗺️ Województwo <span class="text-red-400">*</span></label>
                <select name="voivodeship" class="input-field">
                    <option value="">Wybierz województwo</option>
                    @foreach($voivodeships as $voivodeship)
                    <option value="{{ $voivodeship }}" @selected(($event['voivodeship'] ?? '') === $voivodeship)>{{ $voivodeship }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📝 Opis <span class="text-red-400">*</span></label>
                <textarea name="description" rows="4" placeholder="Program, godziny, wstęp, kontakt do organizatora..." class="input-field resize-none">{{ $event['description'] ?? '' }}</textarea>
            </div>

            {{-- Location --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📍 Miejsce imprezy <span class="text-red-400">*</span></label>
                <p class="text-xs text-gray-500 mb-2 ml-1">Dotknij mapy lub przeciągnij pinezkę, aby zmienić miejsce.</p>
                <div id="map-picker"></div>
                <div class="flex items-center justify-between mt-2">
                    <p id="locationStatus" class="text-xs text-gray-500 ml-1">Nie wybrano miejsca</p>
                    <button type="button" id="locateBtn" class="text-amber-400 text-xs font-semibold whitespace-nowrap">🎯 Moja lokalizacja</button>
                </div>
                <input type="hidden" name="latitude" id="lat" value="{{ $event['latitude'] ?? '' }}">
                <input type="hidden" name="longitude" id="lng" value="{{ $event['longitude'] ?? '' }}">
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">Zapisz zmiany</button>
        </form>
        <div class="h-8"></div>
    </div>

</div>

<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p class="mt-4 text-sm text-gray-300">Zapisywanie…</p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ----- Photo preview -----
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');

    photoInput.addEventListener('change', () => {
        const file = photoInput.files[0];
        if (!file) { return; }
        photoPreview.src = URL.createObjectURL(file);
        photoPreview.classList.remove('hidden');
        photoPlaceholder.classList.add('hidden');
    });

    // ----- Map picker -----
    const INITIAL_LAT = {{ is_numeric($event['latitude'] ?? null) ? $event['latitude'] : 52.0 }};
    const INITIAL_LNG = {{ is_numeric($event['longitude'] ?? null) ? $event['longitude'] : 19.0 }};
    const HAS_LOCATION = {{ is_numeric($event['latitude'] ?? null) ? 'true' : 'false' }};

    const map = L.map('map-picker', {
        center: [INITIAL_LAT, INITIAL_LNG],
        zoom: HAS_LOCATION ? 13 : 5,
        zoomControl: true,
        attributionControl: false,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const pinIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41],
    });

    let marker = null;
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const locationStatus = document.getElementById('locationStatus');

    function placeMarker(lat, lng) {
        if (marker) { map.removeLayer(marker); }
        marker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            setLocation(pos.lat, pos.lng);
        });
        setLocation(lat, lng);
    }

    function setLocation(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
        locationStatus.textContent = '📍 ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
        locationStatus.classList.remove('text-gray-500');
        locationStatus.classList.add('text-green-400');
    }

    if (HAS_LOCATION) { placeMarker(INITIAL_LAT, INITIAL_LNG); }

    map.on('click', e => placeMarker(e.latlng.lat, e.latlng.lng));

    document.getElementById('locateBtn').addEventListener('click', () => {
        if (!navigator.geolocation) { alert('Twoje urządzenie nie udostępnia lokalizacji.'); return; }
        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 13);
            placeMarker(latitude, longitude);
        }, () => alert('Nie udało się pobrać lokalizacji.'), { enableHighAccuracy: true, timeout: 10000 });
    });

    // ----- Submit -----
    document.getElementById('eventForm').addEventListener('submit', function (e) {
        e.preventDefault();

        if (!latInput.value || !lngInput.value) {
            alert('Zaznacz miejsce imprezy na mapie.');
            document.getElementById('map-picker').scrollIntoView({ behavior: 'smooth' });
            return;
        }

        const overlay = document.getElementById('loadingOverlay');
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        overlay.classList.remove('hidden');

        const resetForm = () => {
            overlay.classList.add('hidden');
            btn.disabled = false;
        };

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(async r => {
            if (r.redirected && r.url) {
                window.location.href = r.url;
                return { handled: true };
            }
            const text = await r.text();
            let data = null;
            try { data = text ? JSON.parse(text) : null; } catch (_) { /* not JSON */ }
            return { status: r.status, ok: r.ok, data };
        })
        .then(({ handled, status, ok, data }) => {
            if (handled) { return; }
            if (ok && data && data.redirect) { window.location.href = data.redirect; return; }
            resetForm();

            if (data && data.errors) {
                alert(Object.values(data.errors).flat().join('\n'));
                return;
            }
            if (data && data.message) {
                alert(data.message);
                return;
            }
            if (status === 419) {
                alert('Sesja wygasła. Odśwież stronę i spróbuj ponownie.');
                return;
            }
            alert('Wystąpił błąd (' + status + '). Spróbuj ponownie.');
        })
        .catch(() => {
            resetForm();
            alert('Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.');
        });
    });
})();
</script>
@endpush
