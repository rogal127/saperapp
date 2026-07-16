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

            <button type="button" id="manualCoordsToggle" class="text-xs text-amber-400 font-semibold mb-3 block mx-auto">
                ✏️ Wpisz współrzędne ręcznie
            </button>
            <div id="manualCoordsPanel" class="hidden mb-3">
                <div class="flex gap-2 mb-2">
                    <input type="text" id="manualLat" inputmode="decimal" placeholder="Szerokość (np. 51.1079)"
                        class="input-field text-sm flex-1">
                    <input type="text" id="manualLng" inputmode="decimal" placeholder="Długość (np. 17.0385)"
                        class="input-field text-sm flex-1">
                </div>
                <button type="button" id="manualCoordsApply" class="w-full text-sm font-semibold py-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 active:opacity-70">
                    Zastosuj współrzędne
                </button>
                <p id="manualCoordsError" class="text-red-400 text-xs mt-1 text-center hidden"></p>
            </div>

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

                {{-- Expedition (poszukiwanie) --}}
                <div id="expeditionSection" class="hidden card border border-teal-500/30 bg-teal-500/5">
                    <label class="block text-sm font-semibold text-teal-300 mb-1.5">
                        🧭 Poszukiwanie
                    </label>
                    <div id="expeditionBody"></div>
                    <p class="text-xs text-gray-400 mt-2">Uzupełnia się automatycznie, gdy pinezka leży w terenie poszukiwania. Możesz to zmienić.</p>
                    <p class="text-xs text-amber-300/80 mt-1">Jeśli przypniesz znalezisko do poszukiwania, jego kierownik je zobaczy — także gdy oznaczysz je jako prywatne.</p>
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
                            value="{{ old('depth_cm', 0) }}"
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

                {{-- Report (PDF) --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                        📄 Załącz sprawozdanie <span class="text-gray-500 font-normal">(opcjonalne, PDF)</span>
                    </label>
                    <label id="reportTile" class="flex items-center gap-3 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors">
                        <span class="text-2xl">📄</span>
                        <span id="reportTileText" class="text-sm text-gray-400 flex-1 min-w-0 truncate">Dotknij, aby wybrać plik PDF</span>
                        <input type="file" name="report" accept="application/pdf,.pdf" class="hidden" id="reportInput">
                    </label>
                    <div id="reportSelected" class="hidden card mt-2 flex items-center gap-3">
                        <span class="text-xl">📄</span>
                        <span id="reportName" class="text-sm text-gray-200 flex-1 min-w-0 truncate"></span>
                        <button type="button" id="reportRemove" class="text-red-400 text-sm font-semibold whitespace-nowrap">Usuń</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-1">Sprawozdanie widzisz tylko Ty. Maks. 20 MB.</p>
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
    <div id="loadingSpinner" class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p id="loadingText" class="mt-4 text-sm text-gray-300">Przetwarzanie…</p>
    <div id="progressContainer" class="hidden w-56 mt-3">
        <div class="w-full bg-gray-700/50 rounded-full h-2.5 overflow-hidden">
            <div id="progressBar" class="bg-amber-500 h-full rounded-full transition-all duration-200 ease-out" style="width: 0%"></div>
        </div>
        <p id="progressText" class="text-xs text-gray-500 mt-1.5 text-center">0%</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // --- Auto-zapis wersji roboczej ---------------------------------------
    // Chroni wpisane dane (zwłaszcza długi opis) przed utratą, gdy sesja
    // wygaśnie w trakcie edycji. Draft żyje w localStorage i wraca po
    // ponownym zalogowaniu; kasujemy go dopiero po udanym zapisie.
    const DRAFT_KEY = 'finding_draft_v1';
    const DRAFT_FIELDS = ['name', 'depth_cm', 'description', 'private_notes'];
    const draftForm = document.getElementById('findingForm');

    function saveDraft() {
        try {
            const draft = {};
            DRAFT_FIELDS.forEach(n => {
                const el = draftForm.elements[n];
                if (el) { draft[n] = el.value; }
            });
            const priv = draftForm.elements['is_private'];
            if (priv) { draft.is_private = priv.checked; }
            const type = draftForm.querySelector('input[name="type"]:checked');
            if (type) { draft.type = type.value; }
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch (e) {}
    }

    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
    }

    function restoreDraft() {
        let draft;
        try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY)); } catch (e) { return; }
        if (!draft) { return; }
        DRAFT_FIELDS.forEach(n => {
            const el = draftForm.elements[n];
            // Nie nadpisujemy danych wstawionych serwerowo przez old() (błąd walidacji).
            if (el && !el.value && draft[n]) { el.value = draft[n]; }
        });
        if (draft.is_private && draftForm.elements['is_private'] && !draftForm.elements['is_private'].checked) {
            draftForm.elements['is_private'].checked = true;
        }
        if (draft.type && !draftForm.querySelector('input[name="type"]:checked')) {
            const radio = draftForm.querySelector('input[name="type"][value="' + draft.type + '"]');
            const label = radio ? radio.closest('.finding-type-btn') : null;
            if (label) { label.click(); }
        }
    }

    draftForm.addEventListener('input', saveDraft);
    draftForm.addEventListener('change', saveDraft);
    restoreDraft();

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
    const EXPEDITIONS_URL = "{{ route('expeditions.api') }}";
    const OLD_EXPEDITION_ID = @json((int) old('expedition_id', 0) ?: null);

    let expeditionCandidates = [];
    let expeditionSelect = null;
    let pendingLocation = null; // { lat, lng } captured before expeditions finished loading
    let expeditionAreasDrawn = false;

    // Draw the areas of the user's active expeditions on the picker map so the
    // user can see where their ongoing searches are while choosing a location.
    function drawExpeditionAreas(items) {
        if (expeditionAreasDrawn || typeof map === 'undefined') { return; }
        items.forEach(e => {
            if (!e.area || !e.area.coordinates) { return; }
            const layer = L.geoJSON(e.area, {
                style: {
                    color: '#14b8a6',
                    weight: 2,
                    opacity: 0.9,
                    fillColor: '#14b8a6',
                    fillOpacity: 0.12,
                },
            }).addTo(map);
            layer.bindTooltip(
                `🧭 ${e.name}`,
                { direction: 'center', className: 'leaflet-tooltip-dark', sticky: true }
            );
        });
        expeditionAreasDrawn = true;
    }

    // Ray-casting point-in-polygon test. `ring` is a GeoJSON coordinate ring:
    // an array of [lng, lat] pairs.
    function pointInPolygon(lat, lng, ring) {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const xi = ring[i][0], yi = ring[i][1];
            const xj = ring[j][0], yj = ring[j][1];
            const intersects = ((yi > lat) !== (yj > lat))
                && (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi);
            if (intersects) { inside = !inside; }
        }
        return inside;
    }

    // Whether a point falls inside a GeoJSON area (Polygon or MultiPolygon).
    function areaContainsPoint(area, lat, lng) {
        if (!area || !area.coordinates) { return false; }
        const polygons = area.type === 'MultiPolygon' ? area.coordinates : [area.coordinates];
        return polygons.some(poly => {
            const ring = poly && poly[0];
            return ring && pointInPolygon(lat, lng, ring);
        });
    }

    // Auto-fill the expedition select only when the pin actually falls inside
    // one of the user's active expedition areas — never guess otherwise.
    function autoSelectExpeditionForLocation(lat, lng) {
        if (!expeditionSelect) { pendingLocation = { lat, lng }; return; }
        const match = expeditionCandidates.find(e => areaContainsPoint(e.area, lat, lng));
        expeditionSelect.value = match ? String(match.id) : '';
    }

    (function loadExpeditions() {
        const section = document.getElementById('expeditionSection');
        const body = document.getElementById('expeditionBody');
        fetch(EXPEDITIONS_URL + '?scope=mine')
            .then(r => r.json())
            .then(res => {
                const items = (res.data || []).filter(e => e.status === 'published' && e.phase === 'active');
                if (!items.length) { return; }
                expeditionCandidates = items;
                drawExpeditionAreas(items);

                const select = document.createElement('select');
                select.name = 'expedition_id';
                select.className = 'input-field';
                const def = document.createElement('option');
                def.value = '';
                def.textContent = '— Nie przypisuj —';
                def.selected = !OLD_EXPEDITION_ID;
                select.appendChild(def);
                items.forEach(e => {
                    const opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.name;
                    if (OLD_EXPEDITION_ID === e.id) { opt.selected = true; }
                    select.appendChild(opt);
                });
                body.appendChild(select);
                section.classList.remove('hidden');
                expeditionSelect = select;

                if (!OLD_EXPEDITION_ID && pendingLocation) {
                    autoSelectExpeditionForLocation(pendingLocation.lat, pendingLocation.lng);
                }
            })
            .catch(() => {});
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
    let accuracyCircle = null; // koło dokładności GPS (błąd pomiaru w metrach)
    const pinMarkers = {};    // pinId → L.marker

    // Rysuje koło pokazujące margines błędu GPS (promień = accuracy w metrach).
    function showAccuracyCircle(lat, lng, accuracy) {
        if (accuracyCircle) { map.removeLayer(accuracyCircle); accuracyCircle = null; }
        if (!accuracy || !isFinite(accuracy)) { return; }
        accuracyCircle = L.circle([lat, lng], {
            radius: accuracy,
            color: '#f59e0b',
            weight: 1,
            fillColor: '#f59e0b',
            fillOpacity: 0.12,
            interactive: false,
        }).addTo(map);
    }

    // Zwraca opis jakości sygnału na podstawie dokładności (w metrach).
    function accuracyLabel(accuracy) {
        const m = Math.round(accuracy);
        if (accuracy <= 10)  { return { text: `± ${m} m · dokładny sygnał`, emoji: '🟢' }; }
        if (accuracy <= 50)  { return { text: `± ${m} m · dobra dokładność`, emoji: '🟡' }; }
        return { text: `± ${m} m · słaby sygnał`, emoji: '🔴' };
    }

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
        autoSelectExpeditionForLocation(parseFloat(pin.latitude), parseFloat(pin.longitude));
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
                    autoSelectExpeditionForLocation(parseFloat(pin.latitude), parseFloat(pin.longitude));
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

        // Usuń koło dokładności GPS — obowiązuje tylko dla pozycji z „Moja pozycja"
        if (accuracyCircle) { map.removeLayer(accuracyCircle); accuracyCircle = null; }

        if (newMarker) { map.removeLayer(newMarker); }
        newMarker = L.marker([lat, lng], { icon: newPinIcon }).addTo(map);

        document.getElementById('lat').value = lat.toFixed(7);
        document.getElementById('lng').value = lng.toFixed(7);
        document.getElementById('coordsLabel').textContent = `📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        locationReady = true;
        document.getElementById('nextBtn').removeAttribute('disabled');
        document.getElementById('nextBtn').classList.remove('opacity-40');
        autoSelectExpeditionForLocation(lat, lng);
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
            const municipality = (a.municipality ?? '').replace(/^gmina\s+/i, '');
            const city        = a.city ?? a.town ?? a.village ?? a.hamlet ?? a.suburb ?? (municipality || '');
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

    // Manual coordinates entry
    const manualToggle = document.getElementById('manualCoordsToggle');
    const manualPanel = document.getElementById('manualCoordsPanel');
    const manualLatInput = document.getElementById('manualLat');
    const manualLngInput = document.getElementById('manualLng');
    const manualError = document.getElementById('manualCoordsError');

    manualToggle.addEventListener('click', () => {
        const open = !manualPanel.classList.contains('hidden');
        manualPanel.classList.toggle('hidden');
        manualToggle.textContent = open ? '✏️ Wpisz współrzędne ręcznie' : '✕ Ukryj';
    });

    function openManualCoords() {
        if (manualPanel.classList.contains('hidden')) {
            manualPanel.classList.remove('hidden');
            manualToggle.textContent = '✕ Ukryj';
            manualLatInput.focus();
        }
    }

    document.getElementById('manualCoordsApply').addEventListener('click', () => {
        const lat = parseFloat(manualLatInput.value.replace(',', '.'));
        const lng = parseFloat(manualLngInput.value.replace(',', '.'));
        manualError.classList.add('hidden');

        if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            manualError.textContent = 'Podaj poprawne współrzędne (szerokość: -90…90, długość: -180…180).';
            manualError.classList.remove('hidden');
            return;
        }

        map.setView([lat, lng], 15);
        placeNewMarker(lat, lng);
        manualPanel.classList.add('hidden');
        manualToggle.textContent = '✏️ Wpisz współrzędne ręcznie';
    });

    document.getElementById('locateBtn').addEventListener('click', () => {
        const btn = document.getElementById('locateBtn');
        const originalText = btn.textContent;

        if (!navigator.geolocation) {
            alert('Ta przeglądarka nie udostępnia lokalizacji. Wpisz współrzędne ręcznie.');
            openManualCoords();
            return;
        }

        if (!window.isSecureContext) {
            alert('Lokalizacja działa tylko na połączeniu HTTPS. Wpisz współrzędne ręcznie.');
            openManualCoords();
            return;
        }

        btn.textContent = '⌛ Lokalizuję…';

        const onSuccess = pos => {
            const { latitude, longitude, accuracy } = pos.coords;
            map.setView([latitude, longitude], 15);
            placeNewMarker(latitude, longitude);
            showAccuracyCircle(latitude, longitude, accuracy);
            const q = accuracyLabel(accuracy);
            document.getElementById('coordsLabel').textContent =
                `📍 ${latitude.toFixed(5)}, ${longitude.toFixed(5)} · ${q.emoji} ${q.text}`;
            btn.textContent = originalText;
        };

        // Krok 1: GPS wysokiej dokładności. Na Androidzie potrafi przekroczyć limit
        // czasu w budynku, dlatego w razie błędu próbujemy jeszcze raz lokalizacją
        // sieciową (Wi-Fi/komórki), która jest szybsza i działa wewnątrz.
        navigator.geolocation.getCurrentPosition(onSuccess, err => {
            if (err.code === err.PERMISSION_DENIED) {
                btn.textContent = originalText;
                alert('Brak zgody na lokalizację. Włącz uprawnienia lokalizacji dla przeglądarki albo wpisz współrzędne ręcznie.');
                openManualCoords();
                return;
            }

            navigator.geolocation.getCurrentPosition(onSuccess, err2 => {
                btn.textContent = originalText;
                const msg = err2.code === err2.TIMEOUT
                    ? 'Nie udało się ustalić lokalizacji (brak sygnału GPS). Wpisz współrzędne ręcznie.'
                    : 'Nie udało się pobrać lokalizacji. Wpisz współrzędne ręcznie.';
                alert(msg);
                openManualCoords();
            }, {
                enableHighAccuracy: false,
                timeout: 20000,
                maximumAge: 60000,
            });
        }, {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 30000,
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

    // Report (PDF) picker
    const reportInput = document.getElementById('reportInput');
    const reportTile = document.getElementById('reportTile');
    const reportSelected = document.getElementById('reportSelected');
    const reportName = document.getElementById('reportName');
    const reportRemove = document.getElementById('reportRemove');

    reportInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { return; }
        reportName.textContent = file.name;
        reportSelected.classList.remove('hidden');
        reportTile.classList.add('hidden');
    });

    reportRemove.addEventListener('click', function () {
        reportInput.value = '';
        reportName.textContent = '';
        reportSelected.classList.add('hidden');
        reportTile.classList.remove('hidden');
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
        e.preventDefault();

        const pinId = document.getElementById('pin_id').value;
        const lat = document.getElementById('lat').value;
        if (!pinId && !lat) {
            showStep(1);
            alert('Zaznacz lokalizację na mapie lub wybierz istniejącą pinezkę!');
            return;
        }

        const btn = document.getElementById('submitBtn');
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        btn.disabled = true;
        btn.classList.add('opacity-60');
        overlay.classList.remove('hidden');

        if (selectedPhotos.length > 0) {
            loadingText.textContent = 'Przetwarzanie zdjęć…';
            btn.textContent = 'Przetwarzanie zdjęć...';

            const dt = new DataTransfer();
            for (let i = 0; i < selectedPhotos.length; i++) {
                loadingText.textContent = 'Przetwarzanie zdjęcia ' + (i + 1) + ' z ' + selectedPhotos.length + '…';
                const resized = await resizeImageFile(selectedPhotos[i], 1920, 0.82);
                dt.items.add(resized);
            }
            photoInput.files = dt.files;
            syncPrivateInputs();
        }

        btn.textContent = 'Przesyłanie...';

        if (selectedPhotos.length > 0) {
            loadingText.textContent = 'Przesyłanie zdjęć…';
            loadingSpinner.classList.add('hidden');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
        } else {
            loadingText.textContent = 'Dodawanie znaleziska…';
        }

        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function (ev) {
            if (!ev.lengthComputable) return;
            var pct = Math.round((ev.loaded / ev.total) * 100);
            progressBar.style.width = pct + '%';
            progressText.textContent = pct + '%';
            if (pct < 100) {
                loadingText.textContent = 'Przesyłanie zdjęć… ' + pct + '%';
            } else {
                loadingText.textContent = 'Zapisywanie na serwerze…';
                loadingSpinner.classList.remove('hidden');
                progressContainer.classList.add('hidden');
            }
        });

        function resetUI() {
            overlay.classList.add('hidden');
            btn.disabled = false;
            btn.textContent = 'Dodaj znalezisko';
            btn.classList.remove('opacity-60');
            loadingSpinner.classList.remove('hidden');
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
        }

        xhr.addEventListener('load', function () {
            if (xhr.status === 401 || xhr.status === 419) {
                // Sesja wygasła w trakcie edycji. Draft jest zapisany lokalnie.
                // Odświeżamy stronę: gdy działa „Pamiętaj mnie", sesja odtworzy
                // się z cookie (bez ponownego logowania); w przeciwnym razie
                // użytkownik trafi na logowanie i wróci tu z odtworzonym opisem.
                alert('Twoja sesja wygasła. Wpisane dane zostały zapisane i wrócą — odświeżam stronę.');
                window.location.reload();
                return;
            }
            try {
                var data = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && data.redirect) {
                    clearDraft();
                    window.location.href = data.redirect;
                    return;
                }
                if (data.errors) {
                    var msgs = Object.values(data.errors).flat();
                    alert(msgs.join('\n'));
                } else if (data.message) {
                    alert(data.message);
                } else {
                    alert('Wystąpił błąd. Spróbuj ponownie.');
                }
            } catch (err) {
                alert('Wystąpił błąd. Spróbuj ponownie.');
            }
            resetUI();
        });

        xhr.addEventListener('error', function () {
            alert('Błąd połączenia. Sprawdź internet i spróbuj ponownie.');
            resetUI();
        });

        xhr.open('POST', this.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });
</script>
@endpush
