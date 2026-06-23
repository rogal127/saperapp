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
    .locate-btn, .layer-btn {
        position: absolute;
        right: 12px;
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
    .locate-btn { bottom: 16px; }
    .layer-btn  { bottom: 66px; }
    .locate-btn:active, .layer-btn:active { opacity: 0.7; }
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
            <button type="button" class="layer-btn" id="layerBtn" onclick="toggleLayer()">
                🛰️ Ortofoto
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

        {{-- Privacy info banner --}}
        <div class="flex items-start gap-3 px-5 py-3 bg-amber-500/10 border-b border-amber-500/20 flex-shrink-0">
            <span class="text-amber-400 text-lg leading-tight mt-0.5">🔒</span>
            <p class="text-xs text-amber-300/90 leading-relaxed">
                <span class="font-semibold">Dokładne współrzędne znaleziska widzisz tylko Ty.</span>
                Inni użytkownicy zobaczą jedynie najbliższą miejscowość.
            </p>
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
                <input type="hidden" name="pin_id" id="pin_id" value="{{ old('pin_id') }}">
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
                        class="input-field resize-none @error('description') border-red-500 @enderror"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Private notes --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        🔐 Notatki prywatne <span class="text-gray-500 font-normal">(opcjonalne)</span>
                    </label>
                    <textarea
                        name="private_notes"
                        placeholder="Notatki widoczne tylko dla Ciebie — współrzędne, szczegóły znaleziska, plany..."
                        rows="3"
                        class="input-field resize-none @error('private_notes') border-red-500 @enderror"
                    >{{ old('private_notes') }}</textarea>
                    @error('private_notes')
                        <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1 ml-1">Tylko Ty widzisz tę treść.</p>
                </div>

                {{-- Private finding (visibility) --}}
                <div>
                    <label class="flex items-center justify-between gap-3 card cursor-pointer">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-300">🙈 Tylko do mojego wglądu</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Znalezisko będzie prywatne — nie zobaczy go nikt poza Tobą.</span>
                        </span>
                        <input type="checkbox" name="is_private" value="1" class="w-5 h-5 accent-amber-500 shrink-0" {{ old('is_private') ? 'checked' : '' }}>
                    </label>
                </div>

                {{-- Photos --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        📷 Zdjęcia <span class="text-gray-500 font-normal">(opcjonalne, do 8)</span>
                    </label>
                    <div id="photoGallery" class="grid grid-cols-3 gap-2">
                        <label id="photoAddTile" class="flex flex-col items-center justify-center gap-1 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors aspect-square">
                            <span class="text-2xl">📷</span>
                            <span class="text-[10px] text-gray-400 text-center px-1">Dodaj zdjęcie</span>
                            <input type="file" name="photos[]" accept="image/*" multiple class="hidden" id="photoInput">
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-1" id="photoHint">Możesz dodać kilka zdjęć — pierwsze będzie głównym.</p>
                    <p class="text-xs text-gray-500 mt-1 ml-1">Dotknij 🔒 na zdjęciu, aby ukryć je przed innymi (zobaczysz je tylko Ty).</p>
                    <div id="photosPrivateContainer" class="hidden"></div>
                </div>

                {{-- Finding type --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        🏛️ Typ znaleziska <span class="text-gray-500 font-normal">(opcjonalny)</span>
                    </label>
                    <div class="flex flex-col gap-2" id="findingTypeGroup">
                        <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ old('type') === 'archaeological_monument' ? 'border-red-500 bg-red-500/10' : 'border-transparent' }}"
                            data-active-border="border-red-500" data-active-bg="bg-red-500/10">
                            <input type="radio" name="type" value="archaeological_monument" class="hidden" {{ old('type') === 'archaeological_monument' ? 'checked' : '' }}>
                            <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-red-500"></span>
                            <span class="text-sm font-medium text-gray-200">Zabytek archeologiczny</span>
                        </label>
                        <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ old('type') === 'monument' ? 'border-yellow-400 bg-yellow-400/10' : 'border-transparent' }}"
                            data-active-border="border-yellow-400" data-active-bg="bg-yellow-400/10">
                            <input type="radio" name="type" value="monument" class="hidden" {{ old('type') === 'monument' ? 'checked' : '' }}>
                            <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-yellow-400"></span>
                            <span class="text-sm font-medium text-gray-200">Zabytek</span>
                        </label>
                        <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ old('type') === 'non_monument' ? 'border-green-500 bg-green-500/10' : 'border-transparent' }}"
                            data-active-border="border-green-500" data-active-bg="bg-green-500/10">
                            <input type="radio" name="type" value="non_monument" class="hidden" {{ old('type') === 'non_monument' ? 'checked' : '' }}>
                            <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-green-500"></span>
                            <span class="text-sm font-medium text-gray-200">Przedmiot niezabytkowy</span>
                        </label>
                    </div>
                </div>

                {{-- Category --}}
                <div id="categorySection">
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        🏷️ Kategoria <span class="text-gray-500 font-normal">(opcjonalna)</span>
                    </label>
                    <div id="categoryBody">
                        <p class="text-xs text-gray-500 ml-1">Ładowanie…</p>
                    </div>
                </div>

                {{-- WKZ Consent --}}
                <div id="wkzConsentSection">
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        📋 Zgoda WKZ <span class="text-gray-500 font-normal">(opcjonalna)</span>
                    </label>
                    <div id="wkzConsentBody">
                        <p class="text-xs text-gray-500 ml-1">Ładowanie…</p>
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

{{-- Loading overlay --}}
<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p id="loadingText" class="mt-4 text-sm text-gray-300">Przetwarzanie…</p>
</div>
@endsection

@push('scripts')
<script>
    // Finding type selector
    document.querySelectorAll('#findingTypeGroup .finding-type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#findingTypeGroup .finding-type-btn').forEach(b => {
                b.classList.remove(b.dataset.activeBorder, b.dataset.activeBg);
                b.classList.add('border-transparent');
            });
            btn.classList.remove('border-transparent');
            btn.classList.add(btn.dataset.activeBorder, btn.dataset.activeBg);
        });
    });

    const PINS_URL = "{{ route('pins.index') }}";
    const WKZ_CONSENTS_URL = "{{ route('wkz-consents.index') }}";
    const CATEGORIES_URL = "{{ route('finding-categories.index') }}";
    const OLD_WKZ_CONSENT_ID = @json((int) old('wkz_consent_id', 0) ?: null);
    const OLD_CATEGORY_ID = @json((int) old('finding_category_id', 0) ?: null);

    (function loadCategories() {
        const body = document.getElementById('categoryBody');
        fetch(CATEGORIES_URL)
            .then(r => r.json())
            .then(categories => {
                if (!Array.isArray(categories) || categories.length === 0) {
                    body.innerHTML = '<p class="text-xs text-gray-500 ml-1">Brak kategorii.</p>';
                    return;
                }
                const select = document.createElement('select');
                select.name = 'finding_category_id';
                select.className = 'input-field';
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = '— Bez kategorii —';
                select.appendChild(defaultOpt);
                categories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    if (OLD_CATEGORY_ID === cat.id) { opt.selected = true; }
                    select.appendChild(opt);
                    (cat.children ?? []).forEach(child => {
                        const childOpt = document.createElement('option');
                        childOpt.value = child.id;
                        childOpt.textContent = '   ↳ ' + child.name;
                        if (OLD_CATEGORY_ID === child.id) { childOpt.selected = true; }
                        select.appendChild(childOpt);
                    });
                });
                body.innerHTML = '';
                body.appendChild(select);
            })
            .catch(() => {
                body.innerHTML = '<p class="text-xs text-gray-500 ml-1">Nie udało się załadować kategorii.</p>';
            });
    })();

    (function loadWkzConsents() {
        const body = document.getElementById('wkzConsentBody');
        fetch(WKZ_CONSENTS_URL)
            .then(r => r.json())
            .then(consents => {
                if (!Array.isArray(consents) || consents.length === 0) {
                    body.innerHTML = `<p class="text-xs text-gray-500 ml-1">Nie masz jeszcze żadnych zgód. <a href="{{ route('profile.show') }}#wkz-consents" class="text-amber-400">Dodaj zgodę w profilu</a>.</p>`;
                    return;
                }
                const select = document.createElement('select');
                select.name = 'wkz_consent_id';
                select.id = 'wkzConsentSelect';
                select.className = 'input-field';
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = '— Nie przypisuj zgody —';
                select.appendChild(defaultOpt);
                consents.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if (OLD_WKZ_CONSENT_ID === c.id) { opt.selected = true; }
                    select.appendChild(opt);
                });
                body.innerHTML = '';
                body.appendChild(select);
                const hint = document.createElement('p');
                hint.className = 'text-xs text-gray-500 mt-1 ml-1';
                hint.innerHTML = `Zarządzaj zgodami w <a href="{{ route('profile.show') }}#wkz-consents" class="text-amber-400">profilu</a>.`;
                body.appendChild(hint);
            })
            .catch(() => {
                body.innerHTML = '<p class="text-xs text-gray-500 ml-1">Nie udało się załadować zgód.</p>';
            });
    })();
    const initialLat = {{ is_numeric(old('latitude')) ? old('latitude') : 52.0 }};
    const initialLng = {{ is_numeric(old('longitude')) ? old('longitude') : 19.0 }};
    const initialZoom = {{ is_numeric(old('latitude')) ? 14 : 6 }};

    const map = L.map('map-picker', {
        center: [initialLat, initialLng],
        zoom: initialZoom,
        zoomControl: true,
        attributionControl: false,
    });

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const orthoLayer = L.tileLayer(
        'https://mapy.geoportal.gov.pl/wss/service/PZGIK/ORTO/WMTS/StandardResolution'
        + '?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=ORTOFOTOMAPA&STYLE=default'
        + '&FORMAT=image%2Fjpeg&TileMatrixSet=EPSG:3857&TileMatrix=EPSG:3857:{z}&TileRow={y}&TileCol={x}',
        { maxZoom: 19 }
    );
    let currentLayer = 'osm';
    osmLayer.addTo(map);

    function toggleLayer() {
        const btn = document.getElementById('layerBtn');
        if (currentLayer === 'osm') {
            map.removeLayer(osmLayer);
            orthoLayer.addTo(map);
            currentLayer = 'ortho';
            btn.textContent = '🗺️ Mapa';
        } else {
            map.removeLayer(orthoLayer);
            osmLayer.addTo(map);
            currentLayer = 'osm';
            btn.textContent = '🛰️ Ortofoto';
        }
    }

    const existingPinIcon = L.divIcon({
        html: '<div style="width:22px;height:22px;background:#f59e0b;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.5)"></div>',
        iconSize: [22, 22], iconAnchor: [11, 22], className: '',
    });
    const selectedPinIcon = L.divIcon({
        html: '<div style="width:26px;height:26px;background:#34d399;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 14px rgba(52,211,153,0.7)"></div>',
        iconSize: [26, 26], iconAnchor: [13, 26], className: '',
    });
    const newPinIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41],
    });

    let newMarker = null;     // marker dla nowej lokalizacji (klik w puste miejsce)
    let selectedPinId = null; // id wybranej istniejącej pinezki
    let locationReady = false;
    const pinMarkers = {};    // pinId → L.marker

    // --- Ładowanie własnych pinezek ---
    fetch(PINS_URL)
        .then(r => r.json())
        .then(pins => {
            if (!Array.isArray(pins.data)) { return; }
            pins.data.forEach(pin => {
                if (!pin.latitude || !pin.longitude) { return; }
                const m = L.marker([pin.latitude, pin.longitude], { icon: existingPinIcon })
                    .addTo(map)
                    .bindTooltip(
                        `📍 ${pin.city ?? 'Pin'}<br><span style="color:#f59e0b;font-size:0.8em">${pin.findings_count ?? 0} znalezisk</span>`,
                        { direction: 'top', offset: [0, -20], className: 'leaflet-tooltip-dark' }
                    );
                m.on('click', (e) => {
                    L.DomEvent.stopPropagation(e);
                    selectExistingPin(pin, m);
                });
                pinMarkers[pin.id] = m;
            });
        })
        .catch(() => {});

    function selectExistingPin(pin, marker) {
        // Resetuj poprzednio wybrany
        if (selectedPinId && pinMarkers[selectedPinId]) {
            pinMarkers[selectedPinId].setIcon(existingPinIcon);
        }
        if (newMarker) {
            map.removeLayer(newMarker);
            newMarker = null;
            clearNewPinInputs();
        }

        marker.setIcon(selectedPinIcon);
        selectedPinId = pin.id;

        document.getElementById('pin_id').value = pin.id;
        document.getElementById('lat').value = '';
        document.getElementById('lng').value = '';
        document.getElementById('coordsLabel').textContent = `📍 Istniejąca pinezka: ${pin.city ?? pin.latitude + ', ' + pin.longitude}`;

        const cityLabel = document.getElementById('cityLabel');
        cityLabel.textContent = pin.city ? `🏘️ ${pin.city}${pin.voivodeship ? ', ' + pin.voivodeship : ''} — dodaj do tej pinezki` : '📍 Dodaj kolejne znalezisko do tej pinezki';
        cityLabel.classList.remove('hidden');

        const loc = pin.city ?? `${parseFloat(pin.latitude).toFixed(4)}, ${parseFloat(pin.longitude).toFixed(4)}`;
        document.getElementById('locationSummary').textContent = `${loc} (istniejąca pinezka)`;

        locationReady = true;
        document.getElementById('nextBtn').removeAttribute('disabled');
        document.getElementById('nextBtn').classList.remove('opacity-40');
    }

    function clearNewPinInputs() {
        document.getElementById('lat').value = '';
        document.getElementById('lng').value = '';
        document.getElementById('city').value = '';
        document.getElementById('city_lat').value = '';
        document.getElementById('city_lng').value = '';
        document.getElementById('voivodeship').value = '';
        document.getElementById('county').value = '';
    }

    @if(old('latitude') && old('longitude'))
        placeNewMarker({{ old('latitude') }}, {{ old('longitude') }});
    @endif
    @if(old('city'))
        const _oldCity = @json(old('city'));
        document.getElementById('cityLabel').textContent = '🏘️ ' + _oldCity;
        document.getElementById('cityLabel').classList.remove('hidden');
        document.getElementById('locationSummary').textContent = _oldCity;
        showStep(2);
    @endif

    @if(!empty($initialPinId))
    // Pinezka z URL — od razu krok 2, bez potrzeby wyboru lokalizacji
    (function () {
        const pinId = {{ (int) $initialPinId }};
        document.getElementById('pin_id').value = pinId;
        locationReady = true;

        // Pokaż placeholder w podsumowaniu lokalizacji podczas ładowania
        document.getElementById('locationSummary').textContent = 'Ładowanie…';

        fetch(PINS_URL)
            .then(r => r.json())
            .then(data => {
                const pins = data.data ?? [];
                const pin  = pins.find(p => p.id === pinId);
                if (pin) {
                    const loc = pin.city
                        ? `${pin.city}${pin.voivodeship ? ', ' + pin.voivodeship : ''}`
                        : `${parseFloat(pin.latitude).toFixed(4)}, ${parseFloat(pin.longitude).toFixed(4)}`;
                    document.getElementById('locationSummary').textContent = loc + ' (istniejąca pinezka)';
                    document.getElementById('cityLabel').textContent = pin.city
                        ? `🏘️ ${pin.city}${pin.voivodeship ? ', ' + pin.voivodeship : ''} — dodaj kolejne znalezisko`
                        : '📍 Dodaj kolejne znalezisko do tej pinezki';
                    document.getElementById('cityLabel').classList.remove('hidden');
                } else {
                    document.getElementById('locationSummary').textContent = 'Istniejąca pinezka';
                }
            })
            .catch(() => {
                document.getElementById('locationSummary').textContent = 'Istniejąca pinezka';
            });

        showStep(2);
    })();
    @endif

    function placeNewMarker(lat, lng) {
        // Odznacz wybraną pinezkę
        if (selectedPinId && pinMarkers[selectedPinId]) {
            pinMarkers[selectedPinId].setIcon(existingPinIcon);
        }
        selectedPinId = null;
        document.getElementById('pin_id').value = '';

        if (newMarker) { map.removeLayer(newMarker); }
        newMarker = L.marker([lat, lng], { icon: newPinIcon }).addTo(map);

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
            voivodeshipInput.value = voivodeship;
            countyInput.value      = county;

            const label = city ? `🏘️ ${city}${voivodeship ? ', ' + voivodeship : ''} — nowa pinezka` : '❓ Nie udało się wykryć miejscowości';
            cityLabel.textContent = label;
            locationSummary.textContent = city
                ? `${city}${voivodeship ? ', ' + voivodeship : ''} (${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)})`
                : `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;

            if (city) {
                const searchQuery = encodeURIComponent(`${city}, ${county}, ${voivodeship}, Polska`);
                return fetch(`https://nominatim.openstreetmap.org/search?q=${searchQuery}&format=json&limit=1&accept-language=pl`, {
                    headers: { 'Accept-Language': 'pl' }
                })
                .then(r => r.json())
                .then(results => {
                    if (results.length > 0) {
                        cityLatInput.value = parseFloat(results[0].lat).toFixed(7);
                        cityLngInput.value = parseFloat(results[0].lon).toFixed(7);
                    } else {
                        cityLatInput.value = parseFloat(data.lat).toFixed(7);
                        cityLngInput.value = parseFloat(data.lon).toFixed(7);
                    }
                });
            } else {
                cityLatInput.value = parseFloat(data.lat).toFixed(7);
                cityLngInput.value = parseFloat(data.lon).toFixed(7);
            }
        })
        .catch(() => {
            cityLabel.textContent = '❓ Nie udało się wykryć miejscowości';
            locationSummary.textContent = `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
        });
    }

    map.on('click', e => placeNewMarker(e.latlng.lat, e.latlng.lng));

    document.getElementById('locateBtn').addEventListener('click', () => {
        if (!navigator.geolocation) { return; }
        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 15);
            placeNewMarker(latitude, longitude);
        }, () => {
            alert('Nie udało się pobrać lokalizacji.');
        });
    });

    function showStep(n) {
        document.getElementById('step1').classList.toggle('active', n === 1);
        document.getElementById('step2').classList.toggle('active', n === 2);
        if (n === 1) {
            setTimeout(() => map.invalidateSize(), 50);
        }
    }

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (!locationReady) { return; }
        showStep(2);
    });

    document.getElementById('backBtn').addEventListener('click', () => showStep(1));
    document.getElementById('changeLocationBtn').addEventListener('click', () => showStep(1));

    // Photo gallery picker (multi)
    const MAX_PHOTOS = 8;
    const photoInput = document.getElementById('photoInput');
    const photoGallery = document.getElementById('photoGallery');
    const photoAddTile = document.getElementById('photoAddTile');
    const photoHint = document.getElementById('photoHint');
    const photosPrivateContainer = document.getElementById('photosPrivateContainer');
    let selectedPhotos = [];  // File[]
    let selectedPrivate = []; // bool[] — równolegle do selectedPhotos

    function renderPhotoGallery() {
        photoGallery.querySelectorAll('.photo-thumb').forEach(el => el.remove());
        selectedPhotos.forEach((file, index) => {
            const isPrivate = selectedPrivate[index];
            const wrap = document.createElement('div');
            wrap.className = 'photo-thumb relative rounded-xl overflow-hidden border-2 aspect-square ' + (isPrivate ? 'border-purple-500' : 'border-amber-500');
            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'absolute top-1 right-1 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center text-base leading-none';
            btn.textContent = '×';
            btn.addEventListener('click', () => {
                selectedPhotos.splice(index, 1);
                selectedPrivate.splice(index, 1);
                renderPhotoGallery();
            });

            const lockBtn = document.createElement('button');
            lockBtn.type = 'button';
            lockBtn.className = 'absolute top-1 left-1 rounded-full w-7 h-7 flex items-center justify-center text-sm leading-none ' + (isPrivate ? 'bg-purple-500 text-white' : 'bg-black/60 text-white/70');
            lockBtn.textContent = isPrivate ? '🔒' : '🔓';
            lockBtn.title = isPrivate ? 'Prywatne — tylko Ty' : 'Publiczne';
            lockBtn.addEventListener('click', () => {
                selectedPrivate[index] = !selectedPrivate[index];
                renderPhotoGallery();
            });

            if (isPrivate) {
                const lockBadge = document.createElement('span');
                lockBadge.className = 'absolute bottom-1 right-1 bg-purple-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded';
                lockBadge.textContent = '🔒 Prywatne';
                wrap.appendChild(lockBadge);
            }
            if (index === 0) {
                const badge = document.createElement('span');
                badge.className = 'absolute bottom-1 left-1 bg-amber-500 text-black text-[10px] font-bold px-1.5 py-0.5 rounded';
                badge.textContent = 'Główne';
                wrap.appendChild(badge);
            }
            wrap.appendChild(img);
            wrap.appendChild(btn);
            wrap.appendChild(lockBtn);
            photoGallery.insertBefore(wrap, photoAddTile);
        });

        const full = selectedPhotos.length >= MAX_PHOTOS;
        photoAddTile.classList.toggle('hidden', full);
        photoHint.textContent = full
            ? 'Osiągnięto limit 8 zdjęć.'
            : (selectedPhotos.length ? `Wybrano ${selectedPhotos.length} z ${MAX_PHOTOS}.` : 'Możesz dodać kilka zdjęć — pierwsze będzie głównym.');
    }

    function syncPrivateInputs() {
        photosPrivateContainer.innerHTML = '';
        selectedPrivate.forEach((isPrivate, index) => {
            if (!isPrivate) { return; }
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'photos_private[]';
            hidden.value = index;
            photosPrivateContainer.appendChild(hidden);
        });
    }

    photoInput.addEventListener('change', function () {
        for (const file of this.files) {
            if (selectedPhotos.length >= MAX_PHOTOS) { break; }
            selectedPhotos.push(file);
            selectedPrivate.push(false);
        }
        this.value = '';
        renderPhotoGallery();
    });

    function resizeImageFile(file, maxPx, quality) {
        return new Promise((resolve) => {
            const img = new Image();
            const url = URL.createObjectURL(file);
            img.onload = () => {
                URL.revokeObjectURL(url);
                let { width, height } = img;
                if (width > maxPx || height > maxPx) {
                    if (width >= height) { height = Math.round(height * maxPx / width); width = maxPx; }
                    else { width = Math.round(width * maxPx / height); height = maxPx; }
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    const resized = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
                    resolve(resized);
                }, 'image/jpeg', quality);
            };
            img.src = url;
        });
    }

    document.getElementById('findingForm').addEventListener('submit', async function (e) {
        const pinId = document.getElementById('pin_id').value;
        const lat   = document.getElementById('lat').value;

        if (!pinId && !lat) {
            e.preventDefault();
            showStep(1);
            alert('Zaznacz lokalizację na mapie lub wybierz istniejącą pinezkę!');
            return;
        }

        const btn     = document.getElementById('submitBtn');
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');

        if (selectedPhotos.length > 0) {
            e.preventDefault();
            btn.disabled = true;
            btn.textContent = 'Przetwarzanie zdjęć...';
            btn.classList.add('opacity-60');
            loadingText.textContent = 'Przetwarzanie zdjęć…';
            overlay.classList.remove('hidden');

            const dt = new DataTransfer();
            for (const file of selectedPhotos) {
                const resized = await resizeImageFile(file, 1920, 0.82);
                dt.items.add(resized);
            }
            photoInput.files = dt.files;
            syncPrivateInputs();
        }

        btn.disabled = true;
        btn.textContent = 'Dodawanie...';
        btn.classList.add('opacity-60');
        loadingText.textContent = 'Dodawanie znaleziska…';
        overlay.classList.remove('hidden');

        if (selectedPhotos.length > 0) {
            this.submit();
        }
    });
</script>
@endpush
