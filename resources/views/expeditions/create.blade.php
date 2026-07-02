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
                <p class="text-xs text-gray-500">Krok 1 z 2 — dotykaj mapę, aby dodać rogi obszaru</p>
            </div>
        </div>

        <div class="relative flex-1">
            <div id="map-draw" class="w-full h-full"></div>
            <button type="button" class="map-btn layer-btn" id="layerBtn" onclick="toggleLayer()">🛰️ Ortofoto</button>
        </div>

        <div class="flex-shrink-0 px-5 py-4 border-t border-surface-card bg-surface">
            <p class="text-xs text-gray-500 mb-3 text-center" id="drawHint">Dodaj co najmniej 3 punkty, aby wyznaczyć teren.</p>
            <button type="button" id="openImportBtn" class="btn-secondary text-sm w-full mb-3" style="padding:0.7rem">📥 Importuj z Google My Maps</button>
            <div class="flex gap-2 mb-3">
                <button type="button" id="undoBtn" class="btn-secondary text-sm" style="padding:0.7rem">↶ Cofnij</button>
                <button type="button" id="clearBtn" class="btn-secondary text-sm" style="padding:0.7rem">🗑️ Wyczyść</button>
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
        <div>
            <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🔗 Link do mapy</label>
            <input type="url" id="importLink" placeholder="https://www.google.com/maps/d/..." class="input-field" autocomplete="off">
            <p class="text-xs text-gray-500 mt-1 ml-1">Mapa musi być udostępniona publicznie (dostępna przez link).</p>
        </div>
        <button type="button" id="importFetchBtn" class="btn-primary">Pobierz obszary</button>
        <p id="importStatus" class="text-sm text-center hidden"></p>
        <div id="importResults" class="flex flex-col gap-2"></div>
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

    let vertices = [];          // [{lat, lng}]
    let vertexMarkers = [];     // L.marker[]
    let polygonLayer = null;

    const drawHint = document.getElementById('drawHint');
    const toDetailsBtn = document.getElementById('toDetailsBtn');

    function redraw() {
        if (polygonLayer) { map.removeLayer(polygonLayer); polygonLayer = null; }
        const latlngs = vertices.map(v => [v.lat, v.lng]);
        if (latlngs.length >= 2) {
            polygonLayer = (latlngs.length >= 3
                ? L.polygon(latlngs, { color: '#f59e0b', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.2 })
                : L.polyline(latlngs, { color: '#f59e0b', weight: 2 })
            ).addTo(map);
        }
        const ready = vertices.length >= 3;
        toDetailsBtn.toggleAttribute('disabled', !ready);
        toDetailsBtn.classList.toggle('opacity-40', !ready);
        drawHint.textContent = vertices.length === 0
            ? 'Dodaj co najmniej 3 punkty, aby wyznaczyć teren.'
            : (ready ? `Teren: ${vertices.length} punktów. Możesz przejść dalej.` : `Punkty: ${vertices.length}/3 — dodaj jeszcze ${3 - vertices.length}.`);
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

    map.on('click', e => addVertex(e.latlng.lat, e.latlng.lng));

    document.getElementById('undoBtn').addEventListener('click', () => {
        if (!vertices.length) { return; }
        vertices.pop();
        const m = vertexMarkers.pop();
        if (m) { map.removeLayer(m); }
        redraw();
    });

    document.getElementById('clearBtn').addEventListener('click', () => {
        vertices = [];
        vertexMarkers.forEach(m => map.removeLayer(m));
        vertexMarkers = [];
        redraw();
    });

    // --- Import z Google My Maps ---
    const IMPORT_URL = "{{ route('expeditions.import-mymaps') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const importOverlay = document.getElementById('importOverlay');
    const importStatus = document.getElementById('importStatus');
    const importResults = document.getElementById('importResults');
    const importFetchBtn = document.getElementById('importFetchBtn');

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

    document.getElementById('openImportBtn').addEventListener('click', () => {
        importOverlay.classList.remove('hidden');
    });

    document.getElementById('importCloseBtn').addEventListener('click', () => {
        importOverlay.classList.add('hidden');
    });

    importFetchBtn.addEventListener('click', () => {
        const link = document.getElementById('importLink').value.trim();
        importResults.innerHTML = '';
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

            setImportStatus(`Znaleziono ${polygons.length} ${polygons.length === 1 ? 'obszar' : 'obszary/-ów'}. Wybierz jeden:`);
            polygons.forEach(p => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'card text-left w-full flex items-center gap-3 cursor-pointer';
                btn.innerHTML = '<span class="text-xl">🗺️</span>'
                    + '<span class="text-sm font-semibold text-gray-200 truncate">' + escapeHtml(p.name) + '</span>';
                btn.addEventListener('click', () => choosePolygon(p));
                importResults.appendChild(btn);
            });
        })
        .catch(() => setImportStatus('Błąd połączenia.', 'error'))
        .finally(() => { importFetchBtn.disabled = false; });
    });

    function choosePolygon(p) {
        const ring = (p.area && p.area.coordinates && p.area.coordinates[0]) || [];
        if (ring.length < 4) { setImportStatus('Ten obszar jest nieprawidłowy.', 'error'); return; }

        // Reset current drawing.
        vertices = [];
        vertexMarkers.forEach(m => map.removeLayer(m));
        vertexMarkers = [];

        // Load ring points, skipping the closing duplicate. Coordinates are [lng, lat].
        for (let i = 0; i < ring.length - 1; i++) {
            addVertex(ring[i][1], ring[i][0]);
        }

        if (polygonLayer) { map.fitBounds(polygonLayer.getBounds(), { padding: [30, 30] }); }
        importOverlay.classList.add('hidden');
    }

    function showStep(n) {
        document.getElementById('step1').classList.toggle('active', n === 1);
        document.getElementById('step2').classList.toggle('active', n === 2);
        if (n === 1) { setTimeout(() => map.invalidateSize(), 50); }
    }

    toDetailsBtn.addEventListener('click', () => {
        if (vertices.length < 3) { return; }
        // Closed GeoJSON ring: [lng, lat], first point repeated at the end.
        const ring = vertices.map(v => [v.lng, v.lat]);
        ring.push([vertices[0].lng, vertices[0].lat]);
        document.getElementById('area').value = JSON.stringify({ type: 'Polygon', coordinates: [ring] });
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

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(({ ok, data }) => {
            if (ok && data.redirect) { window.location.href = data.redirect; return; }
            overlay.classList.add('hidden');
            btn.disabled = false;
            if (data.errors) { alert(Object.values(data.errors).flat().join('\n')); }
            else { alert(data.message || 'Wystąpił błąd.'); }
        })
        .catch(() => {
            overlay.classList.add('hidden');
            btn.disabled = false;
            alert('Błąd połączenia.');
        });
    });
</script>
@endpush
