{{--
    Drawing/import logic for the expedition area screen.

    Must be pushed onto the `scripts` stack before the host view's own script.
    Exposes `window.AreaEditor`:
      · load(geojson)  — draw an existing Polygon/MultiPolygon as saved areas
      · toGeoJson()    — commit the pending polygon and return a MultiPolygon
      · hasArea()      — whether anything is drawn
      · showStep(n)    — switch between #step1 (map) and #step2 (details)
      · invalidate()   — recompute the map size after a layout change
    Confirming the area fills the hidden #area input and fires the `area:confirmed`
    event on `document`.
--}}
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

    /**
     * Turn the saved polygons into a GeoJSON MultiPolygon: each polygon is
     * [outerRing], the ring closed with its first point repeated at the end.
     * Coordinates are [lng, lat].
     */
    function buildGeoJson() {
        const coordinates = committed.map(entry => {
            const ring = entry.latlngs.map(v => [v.lng, v.lat]);
            ring.push([entry.latlngs[0].lng, entry.latlngs[0].lat]);
            return [ring];
        });

        return { type: 'MultiPolygon', coordinates };
    }

    /** Draw a stored Polygon/MultiPolygon as already-saved areas. */
    function loadGeoJson(area) {
        if (!area || !Array.isArray(area.coordinates)) { return; }

        const polygons = area.type === 'Polygon' ? [area.coordinates] : area.coordinates;

        polygons.forEach(rings => {
            const ring = Array.isArray(rings) ? rings[0] : null;
            if (!Array.isArray(ring) || ring.length < 4) { return; }

            // Drop the repeated closing point — vertices are kept open here.
            const latlngs = [];
            for (let i = 0; i < ring.length - 1; i++) {
                latlngs.push({ lat: ring[i][1], lng: ring[i][0] });
            }
            addCommittedPolygon(latlngs);
        });

        fitAll();
    }

    toDetailsBtn.addEventListener('click', () => {
        // Auto-save a valid in-progress polygon before proceeding.
        if (vertices.length >= 3) { commitCurrent(); }
        if (!committed.length) { return; }

        const geojson = buildGeoJson();
        const areaInput = document.getElementById('area');
        if (areaInput) { areaInput.value = JSON.stringify(geojson); }
        document.dispatchEvent(new CustomEvent('area:confirmed', { detail: geojson }));
    });

    window.AreaEditor = {
        map,
        showStep,
        load: loadGeoJson,
        toGeoJson: () => { commitCurrent(); return committed.length ? buildGeoJson() : null; },
        hasArea: () => committed.length > 0 || vertices.length >= 3,
        fit: fitAll,
        invalidate: () => map.invalidateSize(),
    };
</script>
