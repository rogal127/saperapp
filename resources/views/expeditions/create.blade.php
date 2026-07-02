@extends('layouts.app')
@section('title', 'Załóż poszukiwanie')

@push('styles')
<style>
    #map-draw { border-radius: 0; overflow: hidden; }
    .draw-vertex {
        width: 14px; height: 14px; background: #f59e0b;
        border: 2px solid #fff; border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0,0,0,0.5);
    }
    .map-btn {
        position: absolute; right: 12px; z-index: 1000;
        background: #2a2a3e; border: 1px solid #404060; color: #e2e8f0;
        border-radius: 0.75rem; padding: 0.6rem 1rem; font-size: 0.85rem;
        font-weight: 600; display: flex; align-items: center; gap: 0.4rem;
        touch-action: manipulation; box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }
    .map-btn:active { opacity: 0.7; }
    .layer-btn { bottom: 16px; }
    .step-screen { display: none; flex-direction: column; height: 100%; }
    .step-screen.active { display: flex; }
    .screen-scroll { overflow-y: auto; flex: 1; }

    /* Import overlay as a bottom sheet once results are shown, so the preview
       polygons drawn on the map behind it stay visible. */
    #importOverlay.sheet-mode {
        top: auto;
        height: auto;
        max-height: 55vh;
        border-top: 1px solid #2a2a3e;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        box-shadow: 0 -8px 24px rgba(0,0,0,0.45);
    }
    #importOverlay.sheet-mode #importInputBlock { display: none; }
    #importOverlay:not(.sheet-mode) #importPickerHint { display: none; }
    #importOverlay:not(.sheet-mode) #importActions { display: none; }
    #undoBtn:disabled, #commitBtn:disabled { opacity: 0.4; cursor: not-allowed; }
    .import-check { flex-shrink: 0; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- === STEP 1: Draw polygon === --}}
    <div class="step-screen active" id="step1">
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <a href="{{ route('expeditions.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</a>
            <div class="flex-1">
                <h1 class="text-lg font-bold text-white">Zaznacz teren</h1>
                <p class="text-xs text-gray-500">Krok 1 z 2 — narysuj jeden lub więcej obszarów</p>
            </div>
        </div>

        <div class="relative flex-1">
            <div id="map-draw" class="w-full h-full"></div>
            <button type="button" class="map-btn layer-btn" id="layerBtn" onclick="toggleLayer()">🛰️ Ortofoto</button>
        </div>

        <div class="flex-shrink-0 px-5 py-4 border-t border-surface-card bg-surface">
            <p class="text-xs text-gray-500 mb-3 text-center" id="drawHint">Dodaj co najmniej 3 punkty, aby wyznaczyć obszar.</p>
            <button type="button" id="openImportBtn" class="btn-secondary text-sm w-full mb-2" style="padding:0.7rem">📥 Importuj z Google My Maps</button>
            <div class="flex gap-2 mb-2">
                <button type="button" id="undoBtn" class="btn-secondary text-sm flex-1" style="padding:0.7rem" disabled>↶ Cofnij</button>
                <button type="button" id="clearBtn" class="btn-secondary text-sm flex-1" style="padding:0.7rem">🗑️ Wyczyść</button>
                <button type="button" id="commitBtn" class="btn-secondary text-sm flex-1 opacity-40" style="padding:0.7rem" disabled>➕ Kolejny</button>
            </div>
            <button type="button" id="toDetailsBtn" class="btn-primary opacity-40" disabled>Dalej — szczegóły →</button>
        </div>
    </div>

    {{-- === STEP 2: Details === --}}
    <div class="step-screen" id="step2">
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <button type="button" id="backBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</button>
            <div class="flex-1">
                <h1 class="text-lg font-bold text-white">Szczegóły poszukiwania</h1>
                <p class="text-xs text-gray-500">Krok 2 z 2</p>
            </div>
        </div>

        <div class="screen-scroll px-5 py-5">
            <form method="POST" action="{{ route('expeditions.store') }}" id="expeditionForm" class="flex flex-col gap-5">
                @csrf
                <input type="hidden" name="area" id="area" value="{{ old('area') }}">

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🧭 Nazwa <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="np. Rajd nad Wisłą" class="input-field @error('name') border-red-500 @enderror">
                    @error('name')<p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Od <span class="text-red-400">*</span></label>
                        <input type="date" name="starts_at" value="{{ old('starts_at') }}" class="input-field @error('starts_at') border-red-500 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Do <span class="text-red-400">*</span></label>
                        <input type="date" name="ends_at" value="{{ old('ends_at') }}" class="input-field @error('ends_at') border-red-500 @enderror">
                    </div>
                </div>
                @error('ends_at')<p class="text-red-400 text-sm -mt-3 ml-1">{{ $message }}</p>@enderror

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📝 Opis <span class="text-gray-500 font-normal">(opcjonalny)</span></label>
                    <textarea name="description" rows="3" placeholder="Zasady, punkt zbiórki, pozwolenie WKZ..." class="input-field resize-none">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">👁️ Widoczność</label>
                    <div class="flex flex-col gap-2" id="visibilityGroup">
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="private" class="w-5 h-5 accent-amber-500" {{ old('visibility', 'private') === 'private' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🔒 Prywatne</span>
                                <span class="block text-xs text-gray-500">Tylko przez zaproszenie lub kod. Nie pojawia się w „Odkrywaj".</span>
                            </span>
                        </label>
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="public" class="w-5 h-5 accent-amber-500" {{ old('visibility') === 'public' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🌍 Publiczne</span>
                                <span class="block text-xs text-gray-500">Widoczne w „Odkrywaj"; inni mogą prosić o dołączenie.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="flex items-center justify-between gap-3 card cursor-pointer">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-300">📣 Opublikuj od razu</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Inaczej zapisze się jako szkic (draft).</span>
                    </span>
                    <input type="checkbox" name="publish" value="1" class="w-5 h-5 accent-amber-500 shrink-0" {{ old('publish', true) ? 'checked' : '' }}>
                </label>

                <button type="submit" class="btn-primary" id="submitBtn">Utwórz poszukiwanie</button>
            </form>
            <div class="h-8"></div>
        </div>
    </div>

</div>

<div id="importOverlay" class="fixed inset-0 z-[9998] flex flex-col bg-surface hidden">
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0 safe-top">
        <button type="button" id="importCloseBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</button>
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white">Importuj z Google My Maps</h1>
            <p class="text-xs text-gray-500">Wklej link do mapy i wybierz obszar</p>
        </div>
    </div>
    <div class="screen-scroll px-5 py-5 flex flex-col gap-4">
        <div id="importInputBlock" class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🔗 Link do mapy</label>
                <input type="url" id="importLink" placeholder="https://www.google.com/maps/d/..." class="input-field" autocomplete="off">
                <p class="text-xs text-gray-500 mt-1 ml-1">Mapa musi być udostępniona publicznie (dostępna przez link).</p>
            </div>
            <button type="button" id="importFetchBtn" class="btn-primary">Pobierz obszary</button>
        </div>
        <p id="importPickerHint" class="text-xs text-gray-400 text-center">👆 Zaznacz obszary (na mapie lub z listy), a potem je dodaj.</p>
        <p id="importStatus" class="text-sm text-center hidden"></p>
        <div id="importResults" class="flex flex-col gap-2"></div>
    </div>
    <div id="importActions" class="flex gap-2 px-5 py-3 border-t border-surface-card flex-shrink-0">
        <button type="button" id="importAddSelected" class="btn-secondary flex-1 opacity-40" disabled>➕ Dodaj zaznaczone</button>
        <button type="button" id="importAddAll" class="btn-primary flex-1">➕ Dodaj wszystkie</button>
    </div>
</div>

<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p class="mt-4 text-sm text-gray-300">Tworzenie…</p>
</div>
@endsection

@push('scripts')
<script>
    const map = L.map('map-draw', { center: [52.0, 19.0], zoom: 6, zoomControl: true, attributionControl: false });

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
            map.removeLayer(osmLayer); orthoLayer.addTo(map); currentLayer = 'ortho'; btn.textContent = '🗺️ Mapa';
        } else {
            map.removeLayer(orthoLayer); osmLayer.addTo(map); currentLayer = 'osm'; btn.textContent = '🛰️ Ortofoto';
        }
    }

    const vertexIcon = L.divIcon({ className: '', html: '<div class="draw-vertex"></div>', iconSize: [14, 14], iconAnchor: [7, 7] });

    let vertices = [];          // current in-progress polygon [{lat, lng}]
    let vertexMarkers = [];     // markers for the current polygon
    let polygonLayer = null;    // preview layer of the current polygon
    let committed = [];         // finalized polygons: [{ latlngs: [{lat,lng}], layer }]

    const drawHint = document.getElementById('drawHint');
    const toDetailsBtn = document.getElementById('toDetailsBtn');
    const undoBtn = document.getElementById('undoBtn');
    const commitBtn = document.getElementById('commitBtn');

    function redraw() {
        if (polygonLayer) { map.removeLayer(polygonLayer); polygonLayer = null; }
        const latlngs = vertices.map(v => [v.lat, v.lng]);
        if (latlngs.length >= 2) {
            polygonLayer = (latlngs.length >= 3
                ? L.polygon(latlngs, { color: '#f59e0b', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.2 })
                : L.polyline(latlngs, { color: '#f59e0b', weight: 2 })
            ).addTo(map);
        }
        updateControls();
    }

    function updateControls() {
        const currentReady = vertices.length >= 3;
        const hasAreas = committed.length > 0;

        undoBtn.disabled = vertices.length === 0;
        commitBtn.disabled = !currentReady;
        commitBtn.classList.toggle('opacity-40', !currentReady);

        const canProceed = hasAreas || currentReady;
        toDetailsBtn.toggleAttribute('disabled', !canProceed);
        toDetailsBtn.classList.toggle('opacity-40', !canProceed);

        let hint;
        if (!hasAreas && vertices.length === 0) {
            hint = 'Dodaj co najmniej 3 punkty, aby wyznaczyć obszar.';
        } else if (vertices.length > 0 && !currentReady) {
            hint = `Bieżący obszar: ${vertices.length}/3 punktów — dodaj jeszcze ${3 - vertices.length}.`;
        } else {
            const parts = [];
            if (hasAreas) { parts.push(`Zapisane obszary: ${committed.length}`); }
            if (currentReady) { parts.push('bieżący gotowy (użyj „Kolejny", aby dodać następny)'); }
            hint = parts.join(' · ') + '. Możesz przejść dalej.';
        }
        drawHint.textContent = hint;
    }

    function clearCurrent() {
        vertices = [];
        vertexMarkers.forEach(m => map.removeLayer(m));
        vertexMarkers = [];
        if (polygonLayer) { map.removeLayer(polygonLayer); polygonLayer = null; }
    }

    function fitAll() {
        const layers = committed.map(e => e.layer);
        if (polygonLayer) { layers.push(polygonLayer); }
        if (!layers.length) { return; }
        const bounds = layers.reduce((b, l) => b.extend(l.getBounds()), layers[0].getBounds());
        map.fitBounds(bounds, { padding: [30, 30] });
    }

    function addCommittedPolygon(latlngs) {
        const layer = L.polygon(latlngs.map(v => [v.lat, v.lng]), {
            color: '#14b8a6', weight: 2, fillColor: '#14b8a6', fillOpacity: 0.25,
        }).addTo(map);
        const entry = { latlngs, layer };
        layer.bindTooltip('Dotknij, aby usunąć ten obszar', { sticky: true });
        layer.on('click', e => {
            L.DomEvent.stopPropagation(e);
            if (confirm('Usunąć ten obszar?')) { removeCommitted(entry); }
        });
        committed.push(entry);
        updateControls();
        return entry;
    }

    function removeCommitted(entry) {
        map.removeLayer(entry.layer);
        committed = committed.filter(e => e !== entry);
        updateControls();
    }

    function commitCurrent() {
        if (vertices.length < 3) { return false; }
        const latlngs = vertices.map(v => ({ lat: v.lat, lng: v.lng }));
        clearCurrent();
        addCommittedPolygon(latlngs);
        redraw();
        return true;
    }

    function addVertex(lat, lng) {
        const index = vertices.length;
        vertices.push({ lat, lng });
        const marker = L.marker([lat, lng], { icon: vertexIcon, draggable: true }).addTo(map);
        marker.on('drag', e => {
            const p = e.target.getLatLng();
            vertices[vertexMarkers.indexOf(marker)] = { lat: p.lat, lng: p.lng };
            redraw();
        });
        vertexMarkers.push(marker);
        redraw();
    }

    map.on('click', e => {
        // While picking imported areas the sheet is open — don't add vertices.
        if (importOverlay.classList.contains('sheet-mode')) { return; }
        addVertex(e.latlng.lat, e.latlng.lng);
    });

    undoBtn.addEventListener('click', () => {
        if (!vertices.length) { return; }
        vertices.pop();
        const m = vertexMarkers.pop();
        if (m) { map.removeLayer(m); }
        redraw();
    });

    // Clears the in-progress polygon; if none, removes the last saved area.
    document.getElementById('clearBtn').addEventListener('click', () => {
        if (vertices.length) {
            clearCurrent();
            redraw();
        } else if (committed.length) {
            removeCommitted(committed[committed.length - 1]);
        }
    });

    commitBtn.addEventListener('click', () => { commitCurrent(); });

    updateControls();

    // --- Import z Google My Maps ---
    const IMPORT_URL = "{{ route('expeditions.import-mymaps') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const importOverlay = document.getElementById('importOverlay');
    const importStatus = document.getElementById('importStatus');
    const importResults = document.getElementById('importResults');
    const importFetchBtn = document.getElementById('importFetchBtn');
    const importAddSelected = document.getElementById('importAddSelected');
    const importAddAll = document.getElementById('importAddAll');

    let importedPolygons = [];      // areas fetched from Google
    let previewLayers = [];         // L.polygon[] shown on the map for preview
    let selectedImport = new Set(); // indices currently selected

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function setImportStatus(msg, kind) {
        if (!msg) { importStatus.classList.add('hidden'); return; }
        importStatus.textContent = msg;
        importStatus.className = 'text-sm text-center ' + (kind === 'error' ? 'text-red-400' : 'text-gray-400');
        importStatus.classList.remove('hidden');
    }

    function clearPreview() {
        previewLayers.forEach(l => { if (l) { map.removeLayer(l); } });
        previewLayers = [];
    }

    function exitPickerMode() {
        clearPreview();
        importResults.innerHTML = '';
        importedPolygons = [];
        selectedImport.clear();
        importOverlay.classList.remove('sheet-mode');
    }

    document.getElementById('openImportBtn').addEventListener('click', () => {
        importOverlay.classList.remove('hidden');
    });

    document.getElementById('importCloseBtn').addEventListener('click', () => {
        if (importOverlay.classList.contains('sheet-mode')) {
            // Go back to the link input rather than closing outright.
            exitPickerMode();
            setImportStatus('');
            return;
        }
        importOverlay.classList.add('hidden');
    });

    importFetchBtn.addEventListener('click', () => {
        const link = document.getElementById('importLink').value.trim();
        importResults.innerHTML = '';
        clearPreview();
        selectedImport.clear();
        if (!link) { setImportStatus('Wklej link do mapy.', 'error'); return; }

        setImportStatus('Pobieranie…');
        importFetchBtn.disabled = true;

        fetch(IMPORT_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ link }),
        })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(({ ok, data }) => {
            if (!ok) { setImportStatus(data.message || 'Nie udało się pobrać mapy.', 'error'); return; }
            const polygons = data.polygons || [];
            if (!polygons.length) { setImportStatus('Nie znaleziono obszarów.', 'error'); return; }

            importedPolygons = polygons;
            setImportStatus('');
            renderPickerList();
            drawPreview();
            importOverlay.classList.add('sheet-mode');
            setTimeout(() => map.invalidateSize(), 60);
        })
        .catch(() => setImportStatus('Błąd połączenia.', 'error'))
        .finally(() => { importFetchBtn.disabled = false; });
    });

    function renderPickerList() {
        importResults.innerHTML = '';
        importedPolygons.forEach((p, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.index = i;
            btn.className = 'card text-left w-full flex items-center gap-3 cursor-pointer';
            btn.innerHTML =
                '<span class="import-check w-5 h-5 rounded border border-gray-500 flex items-center justify-center text-xs font-bold text-black"></span>'
                + '<span class="text-xl">🗺️</span>'
                + '<span class="text-sm font-semibold text-gray-200 truncate flex-1">' + escapeHtml(p.name) + '</span>';
            btn.addEventListener('click', () => toggleSelect(i));
            importResults.appendChild(btn);
        });
        refreshSelectionUI();
    }

    function ringToLatLngs(p) {
        const ring = (p.area && p.area.coordinates && p.area.coordinates[0]) || [];
        return ring.map(c => [c[1], c[0]]);
    }

    function drawPreview() {
        clearPreview();
        const drawn = [];
        importedPolygons.forEach((p, i) => {
            const latlngs = ringToLatLngs(p);
            if (latlngs.length < 4) { previewLayers.push(null); return; }
            const layer = L.polygon(latlngs, {
                color: '#38bdf8', weight: 2, fillColor: '#38bdf8', fillOpacity: 0.15,
            }).addTo(map);
            layer.bindTooltip(p.name, { direction: 'center', sticky: true });
            layer.on('click', e => { L.DomEvent.stopPropagation(e); toggleSelect(i); });
            previewLayers.push(layer);
            drawn.push(layer);
        });
        if (drawn.length) {
            const bounds = drawn.reduce((b, l) => b.extend(l.getBounds()), drawn[0].getBounds());
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    function styleImport(i, selected) {
        const layer = previewLayers[i];
        if (!layer) { return; }
        layer.setStyle(selected
            ? { color: '#f59e0b', weight: 3, fillColor: '#f59e0b', fillOpacity: 0.35 }
            : { color: '#38bdf8', weight: 2, fillColor: '#38bdf8', fillOpacity: 0.15 });
    }

    function toggleSelect(i) {
        if (selectedImport.has(i)) { selectedImport.delete(i); }
        else { selectedImport.add(i); }
        refreshSelectionUI();
    }

    function refreshSelectionUI() {
        importResults.querySelectorAll('button[data-index]').forEach(btn => {
            const i = Number(btn.dataset.index);
            const on = selectedImport.has(i);
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-amber-500', on);
            const check = btn.querySelector('.import-check');
            if (check) {
                check.classList.toggle('bg-amber-500', on);
                check.classList.toggle('border-amber-500', on);
                check.textContent = on ? '✓' : '';
            }
            styleImport(i, on);
        });
        const n = selectedImport.size;
        importAddSelected.disabled = n === 0;
        importAddSelected.classList.toggle('opacity-40', n === 0);
        importAddSelected.textContent = n > 0 ? `➕ Dodaj zaznaczone (${n})` : '➕ Dodaj zaznaczone';
    }

    function addImported(indices) {
        let added = 0;
        indices.forEach(i => {
            const p = importedPolygons[i];
            const ring = (p && p.area && p.area.coordinates && p.area.coordinates[0]) || [];
            if (ring.length < 4) { return; }
            const latlngs = [];
            for (let j = 0; j < ring.length - 1; j++) {
                latlngs.push({ lat: ring[j][1], lng: ring[j][0] });
            }
            addCommittedPolygon(latlngs);
            added++;
        });
        if (!added) { setImportStatus('Nie wybrano poprawnych obszarów.', 'error'); return; }
        exitPickerMode();
        importOverlay.classList.add('hidden');
        fitAll();
    }

    importAddSelected.addEventListener('click', () => {
        if (!selectedImport.size) { return; }
        addImported([...selectedImport]);
    });

    importAddAll.addEventListener('click', () => {
        addImported(importedPolygons.map((_, i) => i));
    });

    function showStep(n) {
        document.getElementById('step1').classList.toggle('active', n === 1);
        document.getElementById('step2').classList.toggle('active', n === 2);
        if (n === 1) { setTimeout(() => map.invalidateSize(), 50); }
    }

    toDetailsBtn.addEventListener('click', () => {
        // Auto-save a valid in-progress polygon before proceeding.
        if (vertices.length >= 3) { commitCurrent(); }
        if (!committed.length) { return; }

        // GeoJSON MultiPolygon: each polygon is [outerRing], the ring closed with
        // its first point repeated at the end. Coordinates are [lng, lat].
        const coordinates = committed.map(entry => {
            const ring = entry.latlngs.map(v => [v.lng, v.lat]);
            ring.push([entry.latlngs[0].lng, entry.latlngs[0].lat]);
            return [ring];
        });
        document.getElementById('area').value = JSON.stringify({ type: 'MultiPolygon', coordinates });
        showStep(2);
    });

    document.getElementById('backBtn').addEventListener('click', () => showStep(1));

    document.getElementById('expeditionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!document.getElementById('area').value) {
            showStep(1);
            alert('Najpierw zaznacz teren na mapie.');
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
            // Parse the body defensively: an error page (419/500/redirect to login)
            // may not be JSON, and blindly calling r.json() would throw and hide
            // the real reason behind a generic "connection error".
            const text = await r.text();
            let data = null;
            try { data = text ? JSON.parse(text) : null; } catch (_) { /* not JSON */ }
            return { status: r.status, ok: r.ok, data };
        })
        .then(({ status, ok, data }) => {
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
</script>
@endpush
