{{--
    Map screen used to draw (or redraw) an expedition's area.

    @param string|null $backHref  Link for the back arrow; when null a button
                                  (#areaBackBtn) is rendered instead so the host
                                  view can decide where "back" leads.
    @param string $title
    @param string $subtitle
    @param string $nextLabel      Label of the button that confirms the area.
    @param bool $active           Whether the map screen is the one shown first.
--}}
<div class="step-screen {{ ($active ?? true) ? 'active' : '' }}" id="step1">
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        @if(!empty($backHref))
        <a href="{{ $backHref }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</a>
        @else
        <button type="button" id="areaBackBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</button>
        @endif
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white">{{ $title }}</h1>
            <p class="text-xs text-gray-500">{{ $subtitle }}</p>
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
        <button type="button" id="toDetailsBtn" class="btn-primary opacity-40" disabled>{{ $nextLabel }}</button>
    </div>
</div>
