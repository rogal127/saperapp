@extends('layouts.app')
@section('title', 'Przeglądaj znaleziska')

@push('styles')
<style>
    #browse-map { position: absolute; inset: 0; }
    #map-wrapper { position: relative; flex: 1; overflow: hidden; }

    /* Panel boczny */
    #panel {
        position: absolute; top: 0; right: 0;
        width: 80%; max-width: 300px; height: 100%;
        background: rgba(26, 26, 46, 0.95);
        backdrop-filter: blur(10px);
        border-left: 1px solid #2a2a3e;
        display: flex; flex-direction: column;
        z-index: 800;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    #panel.panel-open { transform: translateX(0); }
    #panel-toggle {
        position: absolute; left: -52px; top: 50%;
        transform: translateY(-50%);
        width: 52px; height: 90px;
        background: rgba(26,26,46,0.92);
        border-radius: 0.75rem 0 0 0.75rem;
        border: 1px solid #2a2a3e; border-right: none;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 4px;
        cursor: pointer; z-index: 801; touch-action: manipulation;
    }
    #toggle-count { font-size: 0.62rem; color: #f59e0b; font-weight: 700; text-align: center; }
    #findings-list { flex: 1; overflow-y: auto; padding: 0.5rem; }
    .finding-item {
        background: #2a2a3e; border-radius: 0.875rem;
        padding: 0.75rem; margin-bottom: 0.5rem;
        cursor: pointer; border: 2px solid transparent;
        transition: border-color 0.15s;
    }
    .finding-item:active, .finding-item.active { border-color: #f59e0b; }
    .finding-item-name { font-weight: 700; font-size: 0.8rem; color: #fff; }
    .finding-item-meta { font-size: 0.7rem; color: #9ca3af; margin-top: 2px; }
    .finding-item-depth { font-size: 0.75rem; color: #f59e0b; margin-top: 4px; font-weight: 600; }

    /* Kontrolki */
    #locate-btn {
        position: absolute; bottom: 90px; right: 12px;
        z-index: 800;
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248; border-radius: 0.875rem;
        padding: 0.6rem 0.875rem; color: #f59e0b;
        font-size: 0.8rem; font-weight: 700;
        cursor: pointer; touch-action: manipulation;
        display: flex; align-items: center; gap: 0.4rem;
    }
    #locate-btn:active { opacity: 0.7; }

    /* Pasek info o poziomie zoom */
    #zoom-info {
        position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
        z-index: 800;
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248; border-radius: 2rem;
        padding: 0.3rem 0.875rem;
        color: #f59e0b; font-size: 0.75rem; font-weight: 700;
        pointer-events: none; white-space: nowrap;
    }

    /* Bąbelki klastrów */
    .cluster-bubble {
        border-radius: 50%; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        font-weight: 700; color: #1a1a2e;
        box-shadow: 0 2px 12px rgba(0,0,0,0.5);
        border: 3px solid rgba(255,255,255,0.3);
        cursor: pointer; line-height: 1.1;
        transition: transform 0.1s;
    }
    .cluster-bubble:active { transform: scale(0.93); }
    .cluster-bubble .cb-count { font-size: 1rem; font-weight: 800; }
    .cluster-bubble .cb-label { font-size: 0.5rem; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.03em; }
    .cb-voivodeship { background: #f59e0b; }
    .cb-county      { background: #60a5fa; }
    .cb-city        { background: #34d399; }

    /* Pinezka własnego znaleziska */
    .my-pin {
        width: 28px; height: 28px;
        background: #f59e0b;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }

    .popup-dark .leaflet-popup-content-wrapper {
        background: #2a2a3e; color: #e2e8f0;
        border: 1px solid #404060; border-radius: 0.875rem;
    }
    .popup-dark .leaflet-popup-tip { background: #2a2a3e; }

    #panel-header { padding: 0.75rem 0.75rem 0.5rem; border-bottom: 1px solid #323248; flex-shrink: 0; }
    #panel-level { font-size: 0.65rem; color: #f59e0b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    #findings-count { font-size: 0.75rem; color: #9ca3af; }
    #empty-state { text-align: center; padding: 2rem 1rem; color: #6b7280; font-size: 0.8rem; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 class="text-lg font-bold text-white flex-1">Przeglądaj znaleziska</h1>
        <a href="{{ route('findings.create') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-xl flex-shrink-0">➕</a>
    </div>

    {{-- Mapa --}}
    <div id="map-wrapper" class="flex-1">
        <div id="browse-map"></div>

        <div id="zoom-info">🗺️ Przybliż, aby zobaczyć szczegóły</div>

        <button id="locate-btn">🎯 Moja pozycja</button>

        {{-- Panel boczny --}}
        <div id="panel">
            <div id="panel-toggle" onclick="togglePanel()">
                <span id="toggle-arrow" style="font-size:1.1rem;color:#e2e8f0">‹</span>
                <span id="toggle-count">—</span>
            </div>
            <div id="panel-header">
                <div id="panel-level">Widok globalny</div>
                <div id="findings-count">Przybliż mapę</div>
            </div>
            <div id="findings-list">
                <div id="empty-state">Przybliż lub przesuń mapę,<br>aby załadować dane</div>
            </div>
        </div>
    </div>

    {{-- Nawigacja --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('home') }}" class="nav-item">
            <span class="nav-icon">🏠</span><span>Główna</span>
        </a>
        <span class="nav-item active">
            <span class="nav-icon">🗺️</span><span>Mapa</span>
        </span>
        <a href="{{ route('findings.create') }}" class="nav-item">
            <span class="nav-icon">➕</span><span>Dodaj</span>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
const CLUSTERS_URL = '{{ route('findings.api') }}';

// Opisy poziomów
const LEVEL_LABELS = {
    voivodeship: 'Województwa',
    county:      'Powiaty / Gminy',
    city:        'Miejscowości',
    finding:     'Twoje znaleziska',
};

// Kolory bąbelków
const LEVEL_CLASS = {
    voivodeship: 'cb-voivodeship',
    county:      'cb-county',
    city:        'cb-city',
};

let markers = [];
let panelOpen = false;
let myFindings = [];

// --- Mapa ---
const map = L.map('browse-map', {
    center: [52.0, 19.0], zoom: 6,
    zoomControl: false, attributionControl: false,
});
L.control.zoom({ position: 'bottomright' }).addTo(map);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

// --- Ikony ---
const myPinIcon = L.divIcon({
    html: '<div class="my-pin"></div>',
    iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -30],
    className: '',
});

function bubbleIcon(count, level) {
    const size = Math.max(42, Math.min(84, 42 + Math.log(count + 1) * 13));
    const cls  = LEVEL_CLASS[level] ?? 'cb-city';
    const label = level === 'voivodeship' ? 'woj.' : level === 'county' ? 'gm.' : 'miejsc.';
    const html = `
        <div class="cluster-bubble ${cls}" style="width:${size}px;height:${size}px">
            <span class="cb-count">${count}</span>
            <span class="cb-label">${label}</span>
        </div>`;
    return L.divIcon({ html, iconSize: [size, size], iconAnchor: [size/2, size/2], className: '' });
}

// --- Ładowanie danych ---
let loadTimer = null;

function scheduleFetch() {
    clearTimeout(loadTimer);
    loadTimer = setTimeout(fetchClusters, 350);
}

function fetchClusters() {
    const zoom   = map.getZoom();
    const bounds = map.getBounds();

    document.getElementById('loading-indicator').style.display = 'block';

    const params = new URLSearchParams({
        zoom,
        sw_lat: bounds.getSouth().toFixed(6),
        sw_lng: bounds.getWest().toFixed(6),
        ne_lat: bounds.getNorth().toFixed(6),
        ne_lng: bounds.getEast().toFixed(6),
    });

    fetch(`${CLUSTERS_URL}?${params}`)
        .then(r => r.json())
        .then(data => renderData(data, zoom))
        .catch(() => {});
}

// --- Renderowanie markerów i panelu ---
function clearMarkers() {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
}

function renderData(items, zoom) {
    clearMarkers();
    myFindings = [];

    if (!items || items.length === 0) {
        updatePanel([], zoom);
        return;
    }

    items.forEach(item => {
        if (item.type === 'cluster') {
            const m = L.marker([item.lat, item.lng], { icon: bubbleIcon(item.count, item.level) })
                .addTo(map);
            m.on('click', () => {
                // Kliknięcie bąbelka → przybliż do tego obszaru
                const nextZoom = {
                    voivodeship: 9,
                    county:      13,
                    city:        15,
                }[item.level] ?? map.getZoom() + 2;
                map.setView([item.lat, item.lng], nextZoom);
            });
            markers.push(m);
        } else if (item.type === 'finding') {
            // Własna pinezka — dokładne miejsce
            const m = L.marker([item.lat, item.lng], { icon: myPinIcon })
                .bindPopup(buildFindingPopup(item), { className: 'popup-dark', maxWidth: 220 })
                .addTo(map);
            markers.push(m);
            myFindings.push({ ...item, marker: m });
        }
    });

    updatePanel(items, zoom);
}

function updatePanel(items, zoom) {
    const clusters = items.filter(i => i.type === 'cluster');
    const findings = items.filter(i => i.type === 'finding');
    const level    = clusters[0]?.level ?? (findings.length ? 'finding' : null);

    document.getElementById('panel-level').textContent =
        LEVEL_LABELS[level] ?? 'Dane mapy';

    document.getElementById('toggle-count').textContent =
        items.length > 0 ? items.length : '—';

    const totalCount = clusters.reduce((s, c) => s + c.count, 0) + findings.length;
    document.getElementById('findings-count').textContent =
        totalCount > 0
            ? `${totalCount} ${totalCount === 1 ? 'znalezisko' : 'znalezisk'} w widoku`
            : 'Brak znalezisk w widoku';

    const list = document.getElementById('findings-list');

    // Na wysokim zoomie (≥14): lista własnych znalezisk
    if (zoom >= 14 && findings.length > 0) {
        list.innerHTML = '';
        findings.forEach((f, i) => {
            const el = document.createElement('div');
            el.className = 'finding-item';
            el.innerHTML = `
                <div class="finding-item-name">🪙 ${escHtml(f.name)}</div>
                <div class="finding-item-meta">📅 ${f.found_at} · ${f.city ?? ''}</div>
                <div class="finding-item-depth">📏 ${f.depth_cm} cm</div>
                ${f.description ? `<div class="finding-item-meta mt-1">${escHtml(f.description.substring(0,60))}${f.description.length>60?'…':''}</div>` : ''}
            `;
            el.addEventListener('click', () => {
                map.setView([f.lat, f.lng], 17);
                myFindings[i]?.marker?.openPopup();
                highlightItem(el);
                if (!panelOpen) togglePanel();
            });
            list.appendChild(el);
        });
        return;
    }

    // Na niskim zoomie: lista klastrów (top 10 wg count)
    if (clusters.length > 0) {
        list.innerHTML = '';
        [...clusters].sort((a,b) => b.count - a.count).slice(0, 10).forEach(c => {
            const el = document.createElement('div');
            el.className = 'finding-item';
            el.innerHTML = `
                <div class="finding-item-name">${escHtml(c.name)}</div>
                <div class="finding-item-depth">${c.count} znalezisk</div>
            `;
            el.addEventListener('click', () => {
                const nextZoom = { voivodeship: 9, county: 13, city: 15 }[c.level] ?? map.getZoom() + 2;
                map.setView([c.lat, c.lng], nextZoom);
                if (!panelOpen) togglePanel();
            });
            list.appendChild(el);
        });
        return;
    }

    list.innerHTML = '<div id="empty-state">Brak znalezisk w tym widoku</div>';
}

function buildFindingPopup(f) {
    return `
        <div style="font-size:0.82rem;min-width:160px">
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:4px">🪙 ${escHtml(f.name)}</div>
            <div style="color:#f59e0b;font-weight:600">📏 ${f.depth_cm} cm głębokości</div>
            <div style="color:#9ca3af;font-size:0.72rem">📅 ${f.found_at} · ${f.city ?? ''}</div>
            ${f.description ? `<div style="margin-top:4px;color:#d1d5db">${escHtml(f.description.substring(0,80))}${f.description.length>80?'…':''}</div>` : ''}
            ${f.photo_url ? `<img src="${f.photo_url}" style="width:100%;border-radius:6px;margin-top:6px;object-fit:cover;max-height:120px">` : ''}
        </div>`;
}

function highlightItem(el) {
    document.querySelectorAll('.finding-item').forEach(e => e.classList.remove('active'));
    el.classList.add('active');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// --- Zoom info pill ---
const ZOOM_LEVEL_TEXT = [
    [0,  7,  '🗺️ Województwa'],
    [8,  11, '🏙️ Powiaty / Gminy'],
    [12, 13, '🏘️ Miejscowości'],
    [14, 19, '📍 Twoje znaleziska'],
];

function updateZoomInfo() {
    const z = map.getZoom();
    const entry = ZOOM_LEVEL_TEXT.find(([min, max]) => z >= min && z <= max);
    document.getElementById('zoom-info').textContent = entry ? entry[2] : '';
}

// --- Eventy mapy ---
map.on('zoomend moveend', () => {
    updateZoomInfo();
    scheduleFetch();
});

// --- Moja pozycja ---
document.getElementById('locate-btn').addEventListener('click', () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(pos => {
        map.setView([pos.coords.latitude, pos.coords.longitude], 14);
    }, () => alert('Nie udało się pobrać lokalizacji.'));
});

// --- Panel ---
function togglePanel() {
    panelOpen = !panelOpen;
    document.getElementById('panel').classList.toggle('panel-open', panelOpen);
    document.getElementById('toggle-arrow').textContent = panelOpen ? '›' : '‹';
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Pierwsze załadowanie
updateZoomInfo();
fetchClusters();
</script>
@endpush
