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
    @media (min-width: 768px) {
        #panel { max-width: 420px; }
    }
    @media (min-width: 1280px) {
        #panel { max-width: 520px; }
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
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    #panel-toggle.has-findings {
        border-color: #f59e0b;
        animation: togglePulse 1.2s ease-in-out infinite;
    }
    @keyframes togglePulse {
        0%, 100% {
            box-shadow: -4px 0 12px rgba(245,158,11,0.3);
            border-color: #f59e0b;
        }
        50% {
            box-shadow: -6px 0 28px rgba(245,158,11,0.8), 0 0 0 3px rgba(245,158,11,0.25);
            border-color: #fbbf24;
        }
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
    #locate-btn, #layer-btn {
        position: absolute;
        right: 12px;
        z-index: 800;
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248; border-radius: 0.875rem;
        padding: 0.6rem 0.875rem; color: #f59e0b;
        font-size: 0.8rem; font-weight: 700;
        cursor: pointer; touch-action: manipulation;
        display: flex; align-items: center; gap: 0.4rem;
    }
    #locate-btn { bottom: 90px; }
    #layer-btn  { bottom: 140px; }
    #locate-btn:active, #layer-btn:active { opacity: 0.7; }

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
    .cluster-bubble .cb-label { font-size: 0.5rem; font-weight: 600; opacity: 0.85; text-align: center; line-height: 1.2; padding: 0 3px; word-break: break-word; }
    .cb-voivodeship { background: #f59e0b; }
    .cb-county      { background: #60a5fa; }
    .cb-city        { background: #34d399; }

    /* Pinezka własna */
    .my-pin-wrap {
        position: relative;
        display: inline-flex;
        align-items: flex-start;
        justify-content: flex-start;
    }
    .my-pin {
        width: 28px; height: 28px;
        background: #f59e0b;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }
    .pin-count-badge {
        position: absolute; top: -6px; right: -10px;
        background: #ef4444; color: #fff;
        font-size: 0.6rem; font-weight: 800;
        border-radius: 999px;
        min-width: 16px; height: 16px;
        padding: 0 4px;
        display: flex; align-items: center; justify-content: center;
        border: 1.5px solid #1a1a2e;
        line-height: 1;
        z-index: 1;
    }
    /* Pinezka cudza */
    .other-pin {
        width: 22px; height: 22px;
        background: #60a5fa;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.4);
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

    /* Modals */
    #pin-modal, #message-modal {
        display: none; position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.75); align-items: flex-end;
        justify-content: center;
    }
    #pin-modal.open, #message-modal.open { display: flex; }
    #modal-sheet, #message-sheet {
        background: #1a1a2e; border-radius: 1.25rem 1.25rem 0 0;
        border: 1px solid #2a2a3e; width: 100%; max-width: 480px;
        max-height: 90vh; overflow-y: auto;
        animation: slideUp 0.25s ease;
    }
    @media (min-width: 768px) {
        #modal-sheet, #message-sheet { max-width: 640px; }
    }
    @media (min-width: 1280px) {
        #modal-sheet, #message-sheet { max-width: 820px; }
    }
    @keyframes slideUp {
        from { transform: translateY(40px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .modal-body { padding: 1rem; }
    .modal-title { font-weight: 700; font-size: 1rem; color: #fff; }
    .modal-close { color: #9ca3af; font-size: 1.4rem; background: none; border: none; cursor: pointer; line-height: 1; }
    .modal-meta-row { font-size: 0.75rem; color: #9ca3af; margin-top: 3px; }
    .modal-location-note { font-size: 0.68rem; color: #6b7280; margin-top: 0.75rem; }
    .modal-textarea {
        width: 100%; background: #2a2a3e; border: 1px solid #404060;
        border-radius: 0.75rem; color: #fff; padding: 0.75rem;
        font-size: 0.82rem; resize: none; outline: none;
        box-sizing: border-box; font-family: inherit;
    }
    .modal-textarea:focus { border-color: #f59e0b; }
    .modal-send-btn {
        margin-top: 0.75rem; width: 100%; padding: 0.8rem;
        background: #f59e0b; color: #1a1a2e; font-weight: 700;
        border: none; border-radius: 0.75rem; cursor: pointer;
        font-size: 0.9rem; transition: opacity 0.15s;
    }
    .modal-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    #modal-status { text-align: center; margin-top: 0.5rem; font-size: 0.78rem; min-height: 1.1em; }

    /* Lista znalezisk w modalu pinezki */
    .pin-finding-card {
        background: #2a2a3e; border-radius: 0.875rem;
        padding: 0.75rem; margin-bottom: 0.5rem;
        border: 1px solid #404060;
        border-left: 3px solid transparent;
    }
    .pin-finding-card[data-type="archaeological_monument"] { border-left-color: #ef4444; }
    .pin-finding-card[data-type="monument"]                { border-left-color: #facc15; }
    .pin-finding-card[data-type="non_monument"]            { border-left-color: #22c55e; }
    .pin-finding-name { font-weight: 700; font-size: 0.85rem; color: #fff; }
    .pin-finding-meta { font-size: 0.72rem; color: #9ca3af; margin-top: 2px; }
    .pin-finding-depth { font-size: 0.75rem; color: #f59e0b; font-weight: 600; margin-top: 3px; }
    .pin-finding-desc { font-size: 0.75rem; color: #d1d5db; margin-top: 4px; white-space: pre-line; }
    .pin-finding-desc.collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pin-desc-toggle {
        display: block; margin-top: 3px; margin-left: auto; background: none; border: none; padding: 0;
        color: #f59e0b; font-size: 0.75rem; font-weight: 600; cursor: pointer;
    }
    .pin-finding-photo { width: 100%; max-height: 220px; object-fit: contain; border-radius: 0.5rem; margin-top: 0.5rem; background: #1a1a2e; cursor: pointer; }
    .pin-finding-gallery { display: flex; gap: 6px; overflow-x: auto; scroll-snap-type: x mandatory; margin-top: 0.5rem; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .pin-finding-gallery::-webkit-scrollbar { display: none; }
    .pin-finding-gallery .pin-finding-photo { flex: 0 0 88%; scroll-snap-align: start; margin-top: 0; }
    .pin-msg-btn {
        margin-top: 0.5rem; width: 100%; padding: 0.5rem 0.75rem;
        background: transparent; border: 1px solid #f59e0b; color: #f59e0b;
        border-radius: 0.625rem; cursor: pointer; font-size: 0.78rem; font-weight: 600;
    }
    .finding-actions { display: flex; gap: 0.4rem; margin-left: auto; flex-shrink: 0; }
    .finding-action-btn {
        width: 32px; height: 32px; border-radius: 0.5rem; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
    }
    .finding-action-btn.edit { background: #3b3b58; color: #a5b4fc; }
    .finding-action-btn.delete { background: #3b1f1f; color: #f87171; }
    .pin-finding-header { display: flex; align-items: flex-start; gap: 0.5rem; }

    /* Tryb przenoszenia pinezki */
    #relocate-overlay {
        display: none; position: absolute; inset: 0; z-index: 900;
        pointer-events: none;
    }
    #relocate-overlay.active { display: block; }
    #relocate-bar {
        position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
        z-index: 901; pointer-events: auto;
        background: rgba(26,26,46,0.95); border: 1px solid #f59e0b;
        border-radius: 1rem; padding: 0.5rem 1rem;
        display: flex; align-items: center; gap: 0.75rem;
        box-shadow: 0 4px 20px rgba(245,158,11,0.3);
    }
    #relocate-bar span { color: #f59e0b; font-size: 0.8rem; font-weight: 700; white-space: nowrap; }
    #relocate-cancel {
        background: #3b3b58; color: #e2e8f0; border: none; border-radius: 0.5rem;
        padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 600; cursor: pointer;
    }
    .modal-relocate-btn {
        margin-top: 0.5rem; width: 100%; padding: 0.6rem;
        background: transparent; border: 1px solid #60a5fa; color: #60a5fa;
        border-radius: 0.75rem; cursor: pointer; font-size: 0.82rem; font-weight: 600;
        transition: opacity 0.15s;
    }
    .modal-relocate-btn:active { opacity: 0.7; }

    /* Crosshair w trybie przenoszenia */
    #relocate-crosshair {
        display: none; position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -100%);
        z-index: 901; pointer-events: none;
        font-size: 2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
    }
    #relocate-overlay.active ~ #relocate-crosshair { display: block; }

    #relocate-confirm {
        display: none; position: absolute; bottom: 100px; left: 50%; transform: translateX(-50%);
        z-index: 901; background: rgba(26,26,46,0.95); border: 1px solid #34d399;
        border-radius: 1rem; padding: 0.75rem 1rem; max-width: 320px; width: 90%;
        box-shadow: 0 4px 20px rgba(52,211,153,0.3);
    }
    #relocate-confirm.active { display: block; }
    #relocate-confirm-city { color: #34d399; font-size: 0.85rem; font-weight: 700; text-align: center; margin-bottom: 0.5rem; }
    #relocate-confirm-btns { display: flex; gap: 0.5rem; }
    #relocate-confirm-btns button { flex: 1; padding: 0.5rem; border: none; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
    .relocate-save { background: #34d399; color: #1a1a2e; }
    .relocate-retry { background: #3b3b58; color: #e2e8f0; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white">Dodane znaleziska</h1>
            @if($findingsCount !== null)
                <p class="text-xs text-gray-400">Łącznie dodano: {{ number_format($findingsCount, 0, ',', ' ') }}</p>
            @endif
        </div>
        <a href="{{ route('findings.create') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-xl flex-shrink-0">➕</a>
    </div>

    {{-- Mapa --}}
    <div id="map-wrapper" class="flex-1">
        <div id="browse-map"></div>

        <div id="zoom-info">🗺️ Przybliż, aby zobaczyć szczegóły</div>

        <button id="locate-btn">🎯 Moja pozycja</button>
        <button id="layer-btn" onclick="toggleLayer()">🛰️ Ortofoto</button>

        {{-- Overlay przenoszenia pinezki --}}
        <div id="relocate-overlay">
            <div id="relocate-bar">
                <span>📌 Kliknij nowe miejsce na mapie</span>
                <button id="relocate-cancel" onclick="cancelRelocate()">Anuluj</button>
            </div>
        </div>
        <div id="relocate-crosshair">📍</div>
        <div id="relocate-confirm">
            <div id="relocate-confirm-city"></div>
            <div id="relocate-confirm-btns">
                <button class="relocate-retry" onclick="retryRelocate()">Wybierz ponownie</button>
                <button class="relocate-save" onclick="saveRelocate()">Przenieś tutaj</button>
            </div>
        </div>

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

    {{-- Modal pinezki --}}
    <div id="pin-modal" onclick="handleModalBackdrop(event)">
        <div id="modal-sheet">
            <div class="modal-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem">
                    <div>
                        <div class="modal-title" id="modal-pin-location"></div>
                        <div class="modal-meta-row" id="modal-pin-finder"></div>
                    </div>
                    <button class="modal-close" onclick="closeModal()">✕</button>
                </div>
                <div id="modal-add-btn-wrap" style="display:none;margin-bottom:0.75rem">
                    <a id="modal-add-btn" href="#" class="modal-send-btn" style="display:block;text-align:center;text-decoration:none">
                        ➕ Dodaj znalezisko do tej pinezki
                    </a>
                    <button id="modal-relocate-btn" class="modal-relocate-btn" onclick="startRelocate()">
                        📌 Przenieś pinezkę
                    </button>
                </div>
                <div id="modal-findings-list">
                    <div id="modal-loading" style="text-align:center;color:#9ca3af;padding:1rem;font-size:0.82rem">Ładowanie…</div>
                </div>
                <div class="modal-location-note">📌 Dokładna lokalizacja znaleziska jest chroniona</div>
            </div>
        </div>
    </div>

    {{-- Modal wiadomości do znalazcy --}}
    <div id="message-modal" onclick="handleMessageBackdrop(event)">
        <div id="message-sheet">
            <div class="modal-body">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">
                    <div class="modal-title" id="msg-modal-title">Napisz wiadomość</div>
                    <button class="modal-close" onclick="closeMessageModal()">✕</button>
                </div>
                <textarea id="modal-msg" class="modal-textarea" rows="3" placeholder="Napisz wiadomość do znalazcy…"></textarea>
                <button id="modal-send" class="modal-send-btn" onclick="sendModalMessage()">Wyślij wiadomość</button>
                <div id="modal-status"></div>
            </div>
        </div>
    </div>

    {{-- Nawigacja --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('profile.show') }}" class="nav-item">
            <span class="nav-icon">👤</span><span>Profil</span>
        </a>
        <span class="nav-item active">
            <span class="nav-icon">🗺️</span><span>Mapa</span>
        </span>
        <a href="{{ route('findings.create') }}" class="nav-item">
            <span class="nav-icon" style="font-weight:900;color:#f59e0b;">+</span><span>Dodaj</span>
        </a>
        <a href="{{ route('messages.index') }}" class="nav-item" id="nav-messages">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
const CLUSTERS_URL  = "{{ route('findings.api') }}";
const PINS_API_BASE = "{{ url('/api/pins') }}";
const MESSAGE_BASE  = "{{ url('/api/findings') }}";
const CREATE_URL    = "{{ route('findings.create') }}";
const CSRF_TOKEN    = '{{ csrf_token() }}';

async function deleteFinding(id, btn) {
    if (!confirm('Usunąć to znalezisko? Tej operacji nie można cofnąć.')) { return; }
    btn.disabled = true;
    btn.textContent = '⏳';
    try {
        const res = await fetch(`/findings/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        });
        if (!res.ok) { throw new Error(); }
        const card = btn.closest('[data-finding-id]');
        card.style.transition = 'opacity 0.2s';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 200);
    } catch {
        btn.disabled = false;
        btn.textContent = '🗑️';
        alert('Nie udało się usunąć znaleziska.');
    }
}

const LEVEL_LABELS = {
    voivodeship: 'Województwa',
    county:      'Powiaty / Gminy',
    city:        'Miejscowości',
    pin:         'Znaleziska',
};

const LEVEL_CLASS = {
    voivodeship: 'cb-voivodeship',
    county:      'cb-county',
    city:        'cb-city',
};

let markers         = [];
let panelOpen       = false;
let allPins         = [];
let lastLevel       = null;
let panelTotalCount = 0;

const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
const orthoLayer = L.tileLayer(
    'https://mapy.geoportal.gov.pl/wss/service/PZGIK/ORTO/WMTS/StandardResolution'
    + '?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=ORTOFOTOMAPA&STYLE=default'
    + '&FORMAT=image%2Fjpeg&TileMatrixSet=EPSG:3857&TileMatrix=EPSG:3857:{z}&TileRow={y}&TileCol={x}',
    { maxZoom: 19 }
);
let currentLayer = 'osm';

const map = L.map('browse-map', {
    center: [52.0, 19.0], zoom: 6,
    zoomControl: false, attributionControl: false,
    layers: [osmLayer],
});
L.control.zoom({ position: 'bottomright' }).addTo(map);

function toggleLayer() {
    const btn = document.getElementById('layer-btn');
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

function myPinIcon(count) {
    const badge = count > 1 ? `<span class="pin-count-badge">${count}</span>` : '';
    return L.divIcon({
        html: `<div class="my-pin-wrap"><div class="my-pin"></div>${badge}</div>`,
        iconSize: [36, 28], iconAnchor: [14, 28], popupAnchor: [0, -30],
        className: '',
    });
}

const otherPinIcon = L.divIcon({
    html: '<div class="other-pin"></div>',
    iconSize: [22, 22], iconAnchor: [11, 22], popupAnchor: [0, -24],
    className: '',
});

function bubbleIcon(count, level, name) {
    const size = Math.max(52, Math.min(90, 52 + Math.log(count + 1) * 13));
    const cls  = LEVEL_CLASS[level] ?? 'cb-city';
    const shortName = formatBubbleName(level, name, size);
    const html = `<div class="cluster-bubble ${cls}" style="width:${size}px;height:${size}px"><span class="cb-count">${count}</span><span class="cb-label">${shortName}</span></div>`;
    return L.divIcon({ html, iconSize: [size, size], iconAnchor: [size/2, size/2], className: '' });
}

function formatBubbleName(level, name, size) {
    if (!name || name === 'Nieznane') { return '?'; }
    const maxChars = Math.floor(size / 5.5);
    let label = name;
    if (level === 'voivodeship') {
        label = label.replace(/^województwo\s+/i, '');
    } else if (level === 'county') {
        label = 'gm. ' + label.replace(/^(gmina|powiat)\s+/i, '');
    }
    return label.length > maxChars ? label.substring(0, maxChars - 1) + '…' : label;
}

let loadTimer = null;
let countyBbox = null;

function scheduleFetch() {
    clearTimeout(loadTimer);
    loadTimer = setTimeout(fetchClusters, 350);
}

function fetchClusters() {
    const zoom = map.getZoom();
    if (zoom <= 9) { countyBbox = null; }

    let sw_lat, sw_lng, ne_lat, ne_lng;
    if (zoom === 12 && countyBbox) {
        ({ sw_lat, sw_lng, ne_lat, ne_lng } = countyBbox);
    } else {
        const bounds = map.getBounds();
        sw_lat = bounds.getSouth().toFixed(6);
        sw_lng = bounds.getWest().toFixed(6);
        ne_lat = bounds.getNorth().toFixed(6);
        ne_lng = bounds.getEast().toFixed(6);
    }

    const params = new URLSearchParams({ zoom, sw_lat, sw_lng, ne_lat, ne_lng });
    fetch(`${CLUSTERS_URL}?${params}`)
        .then(r => r.json())
        .then(data => renderData(data, zoom))
        .catch(() => {});
}

function clearMarkers() {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
}

function renderData(items, zoom) {
    clearMarkers();
    allPins = [];

    if (!items || items.length === 0) {
        updatePanel([], zoom);
        return;
    }

    items.forEach(item => {
        if (item.type === 'cluster' && item.count === 0) { return; }
        if (item.type === 'cluster') {
            const m = L.marker([item.lat, item.lng], { icon: bubbleIcon(item.count, item.level, item.name) }).addTo(map);
            m.on('click', () => {
                if (item.browsable) {
                    openCityFindingsModal(item);
                    return;
                }
                const nextZoom = { voivodeship: 9, county: 10, city: 15 }[item.level] ?? map.getZoom() + 2;
                if (item.level === 'county' && item.sw_lat !== undefined) {
                    countyBbox = {
                        sw_lat: (+item.sw_lat).toFixed(6),
                        sw_lng: (+item.sw_lng).toFixed(6),
                        ne_lat: (+item.ne_lat).toFixed(6),
                        ne_lng: (+item.ne_lng).toFixed(6),
                    };
                }
                map.setView([item.lat, item.lng], nextZoom);
            });
            markers.push(m);
        } else if (item.type === 'pin') {
            const icon = item.is_mine ? myPinIcon(item.findings_count) : otherPinIcon;
            const m = L.marker([item.lat, item.lng], { icon }).addTo(map);
            m.on('click', () => openPinModal(item));
            markers.push(m);
            allPins.push(item);
        }
    });

    updatePanel(items, zoom);
}

function updatePanel(items, zoom) {
    const clusters = items.filter(i => i.type === 'cluster');
    const pins     = items.filter(i => i.type === 'pin');
    const level    = clusters[0]?.level ?? (pins.length ? 'pin' : null);

    document.getElementById('panel-level').textContent = LEVEL_LABELS[level] ?? 'Dane mapy';
    document.getElementById('toggle-count').textContent = items.length > 0 ? items.length : '—';

    const totalFindings = clusters.reduce((s, c) => s + c.count, 0)
        + pins.reduce((s, p) => s + (p.findings_count ?? 1), 0);
    panelTotalCount = totalFindings;
    document.getElementById('findings-count').textContent = totalFindings > 0
        ? `${totalFindings} ${totalFindings === 1 ? 'znalezisko' : 'znalezisk'} w widoku`
        : 'Brak znalezisk w widoku';

    const list       = document.getElementById('findings-list');
    const toggle     = document.getElementById('panel-toggle');
    const isHighZoom = zoom >= 14;
    const browsableClusters = clusters.filter(c => c.browsable);

    toggle.classList.toggle('has-findings', totalFindings > 0 && !panelOpen);

    if (isHighZoom && lastLevel !== 'pin' && !panelOpen && totalFindings > 0) { togglePanel(); }
    lastLevel = isHighZoom ? 'pin' : (level ?? null);

    // Zoom ≥ 14: własne piny i cudze klastry miejskie w jednej liście panelu
    if (isHighZoom && (pins.length > 0 || browsableClusters.length > 0)) {
        list.innerHTML = '';

        pins.forEach(p => {
            const el = document.createElement('div');
            el.className = 'finding-item';
            el.innerHTML = `
                <div class="finding-item-name">🪙 ${escHtml(p.city ?? 'Pinezka')}
                    <span style="color:#f59e0b;font-weight:800;font-size:0.75rem;margin-left:4px">${p.findings_count ?? 1} znalezisk</span>
                </div>
                <div class="finding-item-meta">👤 Twoja pinezka</div>
            `;
            el.addEventListener('click', () => { highlightItem(el); openPinModal(p); });
            list.appendChild(el);
        });

        browsableClusters.forEach(c => {
            const el = document.createElement('div');
            el.className = 'finding-item';
            el.innerHTML = `
                <div class="finding-item-name">🔵 ${escHtml(c.name)}
                    <span style="color:#60a5fa;font-weight:800;font-size:0.75rem;margin-left:4px">${c.count} znalezisk</span>
                </div>
                <div class="finding-item-meta">Kliknij, aby przejrzeć znaleziska</div>
            `;
            el.addEventListener('click', () => { highlightItem(el); openCityFindingsModal(c); });
            list.appendChild(el);
        });

        if (!list.children.length) {
            list.innerHTML = '<div id="empty-state">Brak znalezisk w tym widoku</div>';
        }
        return;
    }

    if (clusters.length > 0) {
        list.innerHTML = '';
        [...clusters].sort((a, b) => b.count - a.count).slice(0, 10).forEach(c => {
            const el = document.createElement('div');
            el.className = 'finding-item';
            el.innerHTML = `
                <div class="finding-item-name">${escHtml(c.name)}</div>
                <div class="finding-item-depth">${c.count} znalezisk</div>
            `;
            el.addEventListener('click', () => {
                const nextZoom = { voivodeship: 9, county: 10, city: 15 }[c.level] ?? map.getZoom() + 2;
                if (c.level === 'county' && c.sw_lat !== undefined) {
                    countyBbox = {
                        sw_lat: (+c.sw_lat).toFixed(6), sw_lng: (+c.sw_lng).toFixed(6),
                        ne_lat: (+c.ne_lat).toFixed(6), ne_lng: (+c.ne_lng).toFixed(6),
                    };
                }
                map.setView([c.lat, c.lng], nextZoom);
                if (!panelOpen) { togglePanel(); }
            });
            list.appendChild(el);
        });
        return;
    }

    list.innerHTML = '<div id="empty-state">Brak znalezisk w tym widoku</div>';
}

function highlightItem(el) {
    document.querySelectorAll('.finding-item').forEach(e => e.classList.remove('active'));
    el.classList.add('active');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// --- Modal znalezisk miasta (cudzy klaster przy zoom ≥ 14) ---
function openCityFindingsModal(cluster) {
    const list    = document.getElementById('modal-findings-list');
    const addWrap = document.getElementById('modal-add-btn-wrap');

    document.getElementById('modal-pin-location').textContent = `🔵 ${cluster.name}`;
    document.getElementById('modal-pin-finder').textContent   = `${cluster.count} znalezisk · inne konta`;
    addWrap.style.display = 'none';

    list.innerHTML = '';
    const findings = cluster.findings ?? [];

    if (!findings.length) {
        list.innerHTML = '<div style="text-align:center;color:#6b7280;padding:1rem;font-size:0.82rem">Brak znalezisk.</div>';
    } else {
        findings.forEach(f => {
            const card = document.createElement('div');
            card.className = 'pin-finding-card';
            if (f.type) { card.dataset.type = f.type; }
            card.innerHTML = `
                <div class="pin-finding-name">🪙 ${escHtml(f.name)}</div>
                <div class="pin-finding-depth">📏 ${f.depth_cm} cm głębokości</div>
                <div class="pin-finding-meta">📅 ${f.found_at} · 👤 ${f.finder_id
                    ? `<a href="/users/${f.finder_id}" style="color:#f59e0b;font-weight:600;text-decoration:none;">${escHtml(f.finder ?? '')}</a>`
                    : escHtml(f.finder ?? '')
                }</div>
                ${descHtml(f.description)}
                ${photosHtml(f)}
                <button class="pin-msg-btn" onclick="openMessageModal(${f.id}, '${escHtml(f.name)}')">💬 Napisz wiadomość</button>
            `;
            list.appendChild(card);
        });
    }

    document.getElementById('pin-modal').classList.add('open');
}

// --- Galeria zdjęć znaleziska (obsługuje tablicę url-i lub obiektów {url}) ---
function photosHtml(f) {
    const photos = (Array.isArray(f.photos) && f.photos.length)
        ? f.photos.map(p => {
            if (typeof p === 'string') { return p; }
            // Prywatne zdjęcia idą przez własny proxy — URL API wymaga nagłówka Bearer, którego <img> nie wyśle.
            return p.is_private ? `/findings/${f.id}/photos/${p.id}` : p.url;
        })
        : (f.photo_url ? [f.photo_url] : []);
    if (!photos.length) { return ''; }
    const imgs = photos.map(u => {
        const e = escHtml(u);
        return `<img class="pin-finding-photo" src="${e}" alt="" onclick="openLightbox('${e}')">`;
    }).join('');
    return photos.length === 1 ? imgs : `<div class="pin-finding-gallery">${imgs}</div>`;
}

// --- Opis znaleziska z przyciskiem "Więcej" ---
function descHtml(description) {
    if (!description) { return ''; }
    const esc = escHtml(description);
    if (description.length <= 120) {
        return `<div class="pin-finding-desc">${esc}</div>`;
    }
    return `<div class="pin-finding-desc collapsed">${esc}</div>`
        + `<button type="button" class="pin-desc-toggle" onclick="toggleDesc(this)">Więcej</button>`;
}

function toggleDesc(btn) {
    const desc = btn.previousElementSibling;
    const expanded = desc.classList.toggle('expanded');
    desc.classList.toggle('collapsed', !expanded);
    btn.textContent = expanded ? 'Mniej' : 'Więcej';
}

// --- Modal pinezki ---
let activeMessageFindingId = null;
let currentModalPin = null;

function openPinModal(pin) {
    currentModalPin = pin;
    const list   = document.getElementById('modal-findings-list');
    const addWrap = document.getElementById('modal-add-btn-wrap');
    const addBtn  = document.getElementById('modal-add-btn');

    document.getElementById('modal-pin-location').textContent = pin.city ? `📍 ${pin.city}` : '📍 Pinezka';
    document.getElementById('modal-pin-finder').innerHTML = pin.is_mine
        ? '👤 Twoja pinezka'
        : (pin.finder_id
            ? `👤 <a href="/users/${pin.finder_id}" style="color:#f59e0b;font-weight:600;text-decoration:none;">${escHtml(pin.finder ?? '')}</a>`
            : '👤 ' + escHtml(pin.finder ?? ''));

    if (pin.is_mine) {
        addBtn.href = `${CREATE_URL}?pin_id=${pin.id}`;
        addWrap.style.display = 'block';
    } else {
        addWrap.style.display = 'none';
    }

    list.innerHTML = '<div id="modal-loading" style="text-align:center;color:#9ca3af;padding:1rem;font-size:0.82rem">Ładowanie…</div>';
    document.getElementById('pin-modal').classList.add('open');

    fetch(`${PINS_API_BASE}/${pin.id}/findings`)
        .then(r => r.json())
        .then(data => {
            const findings = data.data ?? [];
            if (!findings.length) {
                list.innerHTML = '<div style="text-align:center;color:#6b7280;padding:1rem;font-size:0.82rem">Brak znalezisk przy tej pinezce.</div>';
                return;
            }
            list.innerHTML = '';
            findings.forEach(f => {
                const card = document.createElement('div');
                card.className = 'pin-finding-card';
                card.dataset.findingId = f.id;
                if (f.type) { card.dataset.type = f.type; }
                card.innerHTML = `
                    <div class="pin-finding-header">
                        <div class="flex-1 min-w-0">
                            <div class="pin-finding-name">🪙 ${escHtml(f.name)}</div>
                            <div class="pin-finding-depth">📏 ${f.depth_cm} cm głębokości</div>
                            <div class="pin-finding-meta">📅 ${f.found_at}</div>
                        </div>
                        ${pin.is_mine ? `
                        <div class="finding-actions">
                            <a href="/findings/${f.id}/edit" class="finding-action-btn edit" title="Edytuj">✏️</a>
                            <button class="finding-action-btn delete" onclick="deleteFinding(${f.id}, this)" title="Usuń">🗑️</button>
                        </div>` : ''}
                    </div>
                    ${descHtml(f.description)}
                    ${photosHtml(f)}
                    ${!pin.is_mine ? `<button class="pin-msg-btn" onclick="openMessageModal(${f.id}, '${escHtml(f.name)}')">💬 Napisz wiadomość</button>` : ''}
                `;
                list.appendChild(card);
            });
        })
        .catch(() => {
            list.innerHTML = '<div style="text-align:center;color:#f87171;padding:1rem;font-size:0.82rem">Błąd ładowania znalezisk.</div>';
        });
}

function closeModal() {
    document.getElementById('pin-modal').classList.remove('open');
}

function handleModalBackdrop(e) {
    if (e.target === document.getElementById('pin-modal')) { closeModal(); }
}

function openMessageModal(findingId, findingName) {
    activeMessageFindingId = findingId;
    document.getElementById('msg-modal-title').textContent = `Wiadomość o: ${findingName}`;
    document.getElementById('modal-msg').value = '';
    document.getElementById('modal-status').textContent = '';
    document.getElementById('modal-status').style.color = '';
    document.getElementById('modal-send').disabled = false;
    document.getElementById('message-modal').classList.add('open');
}

function closeMessageModal() {
    document.getElementById('message-modal').classList.remove('open');
    activeMessageFindingId = null;
}

function handleMessageBackdrop(e) {
    if (e.target === document.getElementById('message-modal')) { closeMessageModal(); }
}

function sendModalMessage() {
    const body = document.getElementById('modal-msg').value.trim();
    if (!body || !activeMessageFindingId) { return; }

    const btn    = document.getElementById('modal-send');
    const status = document.getElementById('modal-status');
    btn.disabled       = true;
    status.textContent = 'Wysyłanie…';
    status.style.color = '#9ca3af';

    fetch(`${MESSAGE_BASE}/${activeMessageFindingId}/message`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ body }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok) {
            status.textContent = '✓ Wiadomość wysłana!';
            status.style.color = '#34d399';
            document.getElementById('modal-msg').value = '';
            setTimeout(closeMessageModal, 1500);
        } else {
            status.textContent = data.message ?? 'Nie udało się wysłać.';
            status.style.color = '#f87171';
            btn.disabled = false;
        }
    })
    .catch(() => {
        status.textContent = 'Błąd połączenia.';
        status.style.color = '#f87171';
        btn.disabled = false;
    });
}

// --- Zoom info pill ---
const ZOOM_LEVEL_TEXT = [
    [0,  7,  '🗺️ Województwa'],
    [8,  11, '🏙️ Powiaty / Gminy'],
    [12, 13, '🏘️ Miejscowości'],
    [14, 19, '📍 Znaleziska'],
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
    document.getElementById('panel-toggle').classList.toggle('has-findings', panelTotalCount > 0 && !panelOpen);
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// --- Przenoszenie pinezki ---
let relocatePin = null;
let relocateData = null;
let relocateMarker = null;

function startRelocate() {
    relocatePin = currentModalPin;
    closeModal();
    document.getElementById('relocate-overlay').classList.add('active');
    document.getElementById('relocate-confirm').classList.remove('active');
    map.getContainer().style.cursor = 'crosshair';
    map.once('click', onRelocateClick);
}

function cancelRelocate() {
    map.off('click', onRelocateClick);
    document.getElementById('relocate-overlay').classList.remove('active');
    document.getElementById('relocate-confirm').classList.remove('active');
    map.getContainer().style.cursor = '';
    if (relocateMarker) { map.removeLayer(relocateMarker); relocateMarker = null; }
    relocatePin = null;
    relocateData = null;
}

function retryRelocate() {
    document.getElementById('relocate-confirm').classList.remove('active');
    if (relocateMarker) { map.removeLayer(relocateMarker); relocateMarker = null; }
    relocateData = null;
    map.once('click', onRelocateClick);
}

function onRelocateClick(e) {
    const { lat, lng } = e.latlng;

    if (relocateMarker) { map.removeLayer(relocateMarker); }
    relocateMarker = L.marker([lat, lng], { icon: myPinIcon(1) }).addTo(map);

    document.getElementById('relocate-confirm-city').textContent = 'Wyszukiwanie miejscowości…';
    document.getElementById('relocate-confirm').classList.add('active');

    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=pl`)
        .then(r => r.json())
        .then(data => {
            const a = data.address ?? {};
            const municipality = (a.municipality ?? '').replace(/^gmina\s+/i, '');
            const city = a.city ?? a.town ?? a.village ?? a.hamlet ?? a.suburb ?? (municipality || '');
            const voivodeship = a.state ?? '';
            const county = a.county ?? a.municipality ?? '';

            relocateData = { latitude: lat, longitude: lng, city, voivodeship, county, city_lat: null, city_lng: null };

            if (city) {
                const searchQuery = `${city}, ${voivodeship}, Polska`;
                return fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(searchQuery)}&format=json&limit=1&accept-language=pl`)
                    .then(r => r.json())
                    .then(results => {
                        if (results.length > 0) {
                            relocateData.city_lat = parseFloat(results[0].lat);
                            relocateData.city_lng = parseFloat(results[0].lon);
                        } else {
                            relocateData.city_lat = lat;
                            relocateData.city_lng = lng;
                        }
                    });
            } else {
                relocateData.city_lat = lat;
                relocateData.city_lng = lng;
            }
        })
        .then(() => {
            const label = relocateData.city
                ? `📍 ${relocateData.city}` + (relocateData.voivodeship ? `, ${relocateData.voivodeship}` : '')
                : '⚠️ Nie znaleziono miejscowości';
            document.getElementById('relocate-confirm-city').textContent = label;
        })
        .catch(() => {
            document.getElementById('relocate-confirm-city').textContent = '❌ Błąd geokodowania';
            relocateData = null;
        });
}

function saveRelocate() {
    if (!relocatePin || !relocateData) { return; }

    const btn = document.querySelector('.relocate-save');
    btn.disabled = true;
    btn.textContent = 'Zapisywanie…';

    fetch(`${PINS_API_BASE}/${relocatePin.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(relocateData),
    })
    .then(r => {
        if (!r.ok) { throw new Error(); }
        return r.json();
    })
    .then(() => {
        cancelRelocate();
        fetchClusters();
    })
    .catch(() => {
        alert('Nie udało się przenieść pinezki.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Przenieś tutaj';
    });
}

// Pierwsze załadowanie
updateZoomInfo();
fetchClusters();

</script>
@endpush
