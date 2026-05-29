@extends('layouts.app')
@section('title', 'Przeglądaj znaleziska')

@push('styles')
<style>
    #browse-map {
        position: absolute;
        inset: 0;
    }
    #map-wrapper {
        position: relative;
        flex: 1;
        overflow: hidden;
    }
    #panel {
        position: absolute;
        top: 0; right: 0;
        width: 52%;
        max-width: 240px;
        height: 100%;
        background: rgba(26, 26, 46, 0.92);
        backdrop-filter: blur(8px);
        border-left: 1px solid #2a2a3e;
        display: flex;
        flex-direction: column;
        z-index: 800;
        transform: translateX(0);
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    #panel.hidden-panel { transform: translateX(100%); }
    #panel-toggle {
        position: absolute;
        left: -36px; top: 50%;
        transform: translateY(-50%);
        width: 36px; height: 60px;
        background: rgba(26,26,46,0.92);
        border-radius: 0.5rem 0 0 0.5rem;
        border: 1px solid #2a2a3e;
        border-right: none;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        cursor: pointer;
        z-index: 801;
        touch-action: manipulation;
        color: #e2e8f0;
    }
    #findings-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem;
    }
    .finding-item {
        background: #2a2a3e;
        border-radius: 0.875rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color 0.15s;
    }
    .finding-item:active, .finding-item.active { border-color: #f59e0b; }
    .finding-item-name { font-weight: 700; font-size: 0.8rem; color: #fff; }
    .finding-item-meta { font-size: 0.7rem; color: #9ca3af; margin-top: 2px; }
    .finding-item-depth { font-size: 0.75rem; color: #f59e0b; margin-top: 4px; font-weight: 600; }
    #controls-bar {
        position: absolute;
        top: 12px; left: 8px; right: calc(52% + 8px);
        z-index: 800;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    @media (min-width: 480px) {
        #controls-bar { right: calc(240px + 8px); }
    }
    .control-card {
        background: rgba(26,26,46,0.92);
        backdrop-filter: blur(8px);
        border: 1px solid #323248;
        border-radius: 0.875rem;
        padding: 0.6rem 0.75rem;
        color: #e2e8f0;
    }
    #radius-display { font-size: 0.9rem; font-weight: 700; color: #f59e0b; }
    #radius-input { width: 100%; margin-top: 4px; accent-color: #f59e0b; }
    .radius-labels { display: flex; justify-content: space-between; font-size: 0.6rem; color: #6b7280; margin-top: 2px; }
    #locate-browse-btn {
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248;
        border-radius: 0.875rem;
        padding: 0.6rem 0.875rem;
        color: #f59e0b;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        touch-action: manipulation;
        display: flex; align-items: center; gap: 0.4rem;
        white-space: nowrap;
    }
    #locate-browse-btn:active { opacity: 0.7; }
    .radius-circle-pane { z-index: 650; }
    #loading-indicator {
        position: absolute;
        bottom: 80px; left: 50%; transform: translateX(-50%);
        z-index: 900;
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248;
        border-radius: 2rem;
        padding: 0.4rem 1rem;
        color: #f59e0b;
        font-size: 0.8rem;
        display: none;
    }
    .popup-dark .leaflet-popup-content-wrapper {
        background: #2a2a3e; color: #e2e8f0; border: 1px solid #404060; border-radius: 0.875rem;
    }
    .popup-dark .leaflet-popup-tip { background: #2a2a3e; }
    #panel-header {
        padding: 0.75rem 0.75rem 0.5rem;
        border-bottom: 1px solid #323248;
        flex-shrink: 0;
    }
    #findings-count { font-size: 0.75rem; color: #9ca3af; }
    #empty-state { text-align: center; padding: 2rem 1rem; color: #6b7280; font-size: 0.8rem; }
    #search-circle-btn {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #1a1a2e;
        font-weight: 700;
        border-radius: 0.75rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.78rem;
        width: 100%;
        margin-top: 6px;
        cursor: pointer;
        touch-action: manipulation;
        transition: transform 0.1s;
    }
    #search-circle-btn:active { transform: scale(0.97); }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Top bar --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">
            ‹
        </a>
        <h1 class="text-lg font-bold text-white flex-1">Przeglądaj znaleziska</h1>
        @auth
        <a href="{{ route('findings.create') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-xl flex-shrink-0">
            ➕
        </a>
        @endauth
    </div>

    {{-- Map area --}}
    <div id="map-wrapper" class="flex-1">
        <div id="browse-map"></div>

        {{-- Controls (top-left over map) --}}
        <div id="controls-bar">
            <div class="control-card">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-400">Promień</span>
                    <span id="radius-display">10 km</span>
                </div>
                <input type="range" id="radius-input" min="1" max="100" value="10" step="1">
                <div class="radius-labels"><span>1 km</span><span>50</span><span>100 km</span></div>
                <button id="search-circle-btn">🔍 Szukaj w promieniu</button>
            </div>
            <button id="locate-browse-btn">🎯 Moja pozycja</button>
        </div>

        {{-- Loading --}}
        <div id="loading-indicator">⏳ Ładowanie...</div>

        {{-- Right panel --}}
        <div id="panel">
            <div id="panel-toggle" onclick="togglePanel()">‹</div>
            <div id="panel-header">
                <div class="text-sm font-bold text-white">Znaleziska</div>
                <div id="findings-count">Ustaw promień i szukaj</div>
            </div>
            <div id="findings-list">
                <div id="empty-state">
                    Naciśnij „Szukaj w promieniu" aby wczytać znaleziska
                </div>
            </div>
        </div>

    </div>

    {{-- Bottom nav --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('home') }}" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Główna</span>
        </a>
        <span class="nav-item active">
            <span class="nav-icon">🗺️</span>
            <span>Mapa</span>
        </span>
        @auth
        <a href="{{ route('findings.create') }}" class="nav-item">
            <span class="nav-icon">➕</span>
            <span>Dodaj</span>
        </a>
        @endauth
    </div>

</div>
@endsection

@push('scripts')
<script>
    // State
    let circle = null;
    let markers = [];
    let circleCenter = null;
    let panelOpen = true;
    let allFindings = [];

    // Map init (Poland center)
    const map = L.map('browse-map', {
        center: [52.0, 19.0],
        zoom: 6,
        zoomControl: true,
        attributionControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    // Custom marker icons
    const goldIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34],
    });

    const searchPinIcon = L.divIcon({
        html: `<div style="
            width: 32px; height: 32px;
            background: #f59e0b;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        "></div>`,
        iconSize: [32, 32], iconAnchor: [16, 32],
        className: '',
    });

    let searchPin = null;

    // Place search center on map click
    map.on('click', e => {
        setSearchCenter(e.latlng.lat, e.latlng.lng);
    });

    function setSearchCenter(lat, lng) {
        circleCenter = { lat, lng };
        if (searchPin) map.removeLayer(searchPin);
        searchPin = L.marker([lat, lng], { icon: searchPinIcon })
            .bindTooltip('Centrum wyszukiwania', { permanent: false })
            .addTo(map);
        updateCircle();
    }

    // Radius control
    const radiusInput = document.getElementById('radius-input');
    const radiusDisplay = document.getElementById('radius-display');

    radiusInput.addEventListener('input', () => {
        radiusDisplay.textContent = radiusInput.value + ' km';
        updateCircle();
    });

    function updateCircle() {
        if (!circleCenter) return;
        if (circle) map.removeLayer(circle);
        circle = L.circle([circleCenter.lat, circleCenter.lng], {
            radius: radiusInput.value * 1000,
            color: '#f59e0b',
            fillColor: '#f59e0b',
            fillOpacity: 0.06,
            weight: 2,
            dashArray: '6 4',
        }).addTo(map);
    }

    // Search button
    document.getElementById('search-circle-btn').addEventListener('click', () => {
        if (!circleCenter) {
            alert('Dotknij mapę, aby ustawić centrum wyszukiwania!');
            return;
        }
        fetchFindings();
    });

    // Locate me
    document.getElementById('locate-browse-btn').addEventListener('click', () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 13);
            setSearchCenter(latitude, longitude);
        }, () => alert('Nie udało się pobrać lokalizacji.'));
    });

    // Fetch findings from API
    function fetchFindings() {
        const loading = document.getElementById('loading-indicator');
        loading.style.display = 'block';

        clearMarkers();
        document.getElementById('findings-list').innerHTML = '<div id="empty-state">Ładowanie...</div>';

        const url = `{{ route('findings.api') }}?lat=${circleCenter.lat}&lng=${circleCenter.lng}&radius=${radiusInput.value}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                allFindings = data;
                renderFindings(data);
            })
            .catch(() => {
                loading.style.display = 'none';
                document.getElementById('findings-list').innerHTML =
                    '<div id="empty-state">Błąd ładowania danych.</div>';
            });
    }

    function clearMarkers() {
        markers.forEach(m => map.removeLayer(m));
        markers = [];
    }

    function renderFindings(findings) {
        const list = document.getElementById('findings-list');
        const count = document.getElementById('findings-count');

        count.textContent = findings.length
            ? `${findings.length} znalezisk w promieniu ${radiusInput.value} km`
            : 'Brak znalezisk w tej okolicy';

        if (findings.length === 0) {
            list.innerHTML = '<div id="empty-state">Brak znalezisk w wybranym promieniu</div>';
            return;
        }

        list.innerHTML = '';

        findings.forEach((f, i) => {
            // Add map marker
            const marker = L.marker([f.latitude, f.longitude], { icon: goldIcon })
                .bindPopup(buildPopupHtml(f), { className: 'popup-dark', maxWidth: 220 })
                .addTo(map);
            marker.on('click', () => highlightListItem(i));
            markers.push(marker);

            // Add list item
            const item = document.createElement('div');
            item.className = 'finding-item';
            item.id = `fi-${i}`;
            item.innerHTML = `
                <div class="finding-item-name">🪙 ${escHtml(f.name)}</div>
                <div class="finding-item-meta">👤 ${escHtml(f.finder)} · 📅 ${f.found_at}</div>
                <div class="finding-item-meta">📍 ${f.distance} km od centrum</div>
                <div class="finding-item-depth">📏 ${f.depth_cm} cm głębokości</div>
                ${f.description ? `<div class="finding-item-meta mt-1">${escHtml(f.description.substring(0, 60))}${f.description.length > 60 ? '…' : ''}</div>` : ''}
            `;
            item.addEventListener('click', () => {
                map.setView([f.latitude, f.longitude], 15);
                markers[i].openPopup();
                highlightListItem(i);
                if (!panelOpen) togglePanel();
            });
            list.appendChild(item);
        });
    }

    function buildPopupHtml(f) {
        return `
            <div style="font-size:0.82rem; min-width:160px">
                <div style="font-weight:700; font-size:0.92rem; margin-bottom:4px">🪙 ${escHtml(f.name)}</div>
                <div style="color:#9ca3af">👤 ${escHtml(f.finder)}</div>
                <div style="color:#f59e0b; font-weight:600; margin-top:3px">📏 ${f.depth_cm} cm</div>
                <div style="color:#9ca3af; font-size:0.72rem">📅 ${f.found_at}</div>
                ${f.description ? `<div style="margin-top:4px;color:#d1d5db">${escHtml(f.description.substring(0,80))}${f.description.length>80?'…':''}</div>` : ''}
                ${f.photo_url ? `<img src="${f.photo_url}" style="width:100%;border-radius:6px;margin-top:6px;object-fit:cover;max-height:120px">` : ''}
            </div>
        `;
    }

    function highlightListItem(idx) {
        document.querySelectorAll('.finding-item').forEach(el => el.classList.remove('active'));
        const el = document.getElementById(`fi-${idx}`);
        if (el) {
            el.classList.add('active');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function togglePanel() {
        panelOpen = !panelOpen;
        const panel = document.getElementById('panel');
        const toggle = document.getElementById('panel-toggle');
        panel.classList.toggle('hidden-panel', !panelOpen);
        toggle.textContent = panelOpen ? '›' : '‹';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
@endpush
