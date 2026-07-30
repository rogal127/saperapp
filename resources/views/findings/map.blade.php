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

    /* Przełącznik trybu mapy */
    #mode-switch {
        display: inline-flex; background: #2a2a3e;
        border-radius: 0.75rem; padding: 3px; gap: 3px;
        max-width: 100%; overflow-x: auto; flex-wrap: nowrap;
        scrollbar-width: none;
    }
    #mode-switch::-webkit-scrollbar { display: none; }
    .mode-btn {
        padding: 0.35rem 0.75rem; border: none; border-radius: 0.6rem;
        background: transparent; color: #9ca3af;
        font-size: 0.78rem; font-weight: 700; cursor: pointer; white-space: nowrap;
        touch-action: manipulation; transition: background 0.15s, color 0.15s;
    }
    /* Trzy tryby (widok administratora) nie mieszczą się na wąskich ekranach */
    #mode-switch.three-modes .mode-btn { padding: 0.35rem 0.5rem; font-size: 0.7rem; }
    .mode-btn.active { background: #f59e0b; color: #1a1a2e; }

    /* Legenda ról w trybie poszukiwań / stowarzyszeń */
    #exp-legend, #assoc-legend {
        display: none;
        position: absolute; left: 12px; bottom: 90px; z-index: 800;
        background: rgba(26,26,46,0.92);
        border: 1px solid #323248; border-radius: 0.875rem;
        padding: 0.5rem 0.7rem;
        font-size: 0.68rem; font-weight: 600; color: #e2e8f0;
        line-height: 1.6; pointer-events: none;
    }
    #exp-legend.visible, #assoc-legend.visible { display: block; }
    .legend-dot {
        display: inline-block; width: 9px; height: 9px;
        border-radius: 50%; margin-right: 5px; vertical-align: middle;
    }

    /* Pozycje ról na liście panelu */
    .exp-role-dot {
        display: inline-block; width: 9px; height: 9px;
        border-radius: 50%; margin-right: 5px; vertical-align: middle;
    }
    .exp-phase-badge {
        font-size: 0.62rem; font-weight: 700; padding: 1px 6px;
        border-radius: 1rem; margin-left: 4px; white-space: nowrap;
    }

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
    #pin-modal, #message-modal, #share-modal, #assoc-modal {
        display: none; position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.75); align-items: flex-end;
        justify-content: center;
    }
    #pin-modal.open, #message-modal.open, #share-modal.open, #assoc-modal.open { display: flex; }
    #modal-sheet, #message-sheet, #share-sheet, #assoc-sheet {
        background: #1a1a2e; border-radius: 1.25rem 1.25rem 0 0;
        border: 1px solid #2a2a3e; width: 100%; max-width: 480px;
        max-height: 90vh; display: flex; flex-direction: column;
        animation: slideUp 0.25s ease;
    }
    #modal-sheet-header, #message-sheet-header, #share-sheet-header, #assoc-sheet-header {
        flex-shrink: 0;
        padding: 1rem 1rem 0.75rem;
        border-bottom: 1px solid #2a2a3e;
        border-radius: 1.25rem 1.25rem 0 0;
    }
    #modal-sheet-body, #message-sheet-body, #share-sheet-body, #assoc-sheet-body {
        flex: 1; overflow-y: auto;
    }
    @media (min-width: 768px) {
        #modal-sheet, #message-sheet, #share-sheet, #assoc-sheet { max-width: 640px; }
    }
    @media (min-width: 1280px) {
        #modal-sheet, #message-sheet, #share-sheet, #assoc-sheet { max-width: 820px; }
    }
    .autocomplete-list {
        position: relative; z-index: 50; background: #2a2a3e; border: 1px solid #404060;
        border-top: none; border-radius: 0 0 0.75rem 0.75rem; max-height: 200px; overflow-y: auto;
    }
    .autocomplete-item { padding: 0.5rem 0.75rem; font-size: 0.82rem; color: #e2e8f0; cursor: pointer; }
    .autocomplete-item:active { background: #404060; }
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
    #modal-status, #shareStatus { text-align: center; margin-top: 0.5rem; font-size: 0.78rem; min-height: 1.1em; }

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
        margin-top: 0.5rem; width: 100%; padding: 0.4rem 0.4rem;
        background: rgba(245,158,11,0.08); border: none; color: #f0a840;
        border-radius: 0.5rem; cursor: pointer; font-size: 0.7rem; font-weight: 600;
    }
    .finding-actions { display: flex; gap: 0.4rem; margin-left: auto; flex-shrink: 0; }
    .finding-action-btn {
        width: 32px; height: 32px; border-radius: 0.5rem; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
    }
    .finding-action-btn.edit { background: #3b3b58; color: #a5b4fc; }
    .finding-action-btn.delete { background: #3b1f1f; color: #f87171; }
    .pin-finding-header { display: flex; align-items: flex-start; gap: 0.5rem; }
    .like-btn { background: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 0.5rem 0.25rem; flex-shrink: 0; min-height: 44px; }
    .like-btn .like-icon { font-size: 1rem; }
    .like-btn .like-count { font-size: 0.85rem; color: #9ca3af; font-weight: 600; }
    .favorite-btn { background: none; border: none; cursor: pointer; display: inline-flex; align-items: center; padding: 0.5rem 0.25rem; flex-shrink: 0; min-height: 44px; }
    .favorite-btn .favorite-icon { font-size: 1rem; }
    .pin-finding-actions { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.4rem; }

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

    /* Tryb stowarzyszeń (tylko administratorzy) */
    .assoc-pin {
        width: 24px; height: 24px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.5);
    }
    .assoc-pin.known   { background: #22c55e; }
    .assoc-pin.unknown { background: #ef4444; }

    #assoc-place-overlay {
        display: none; position: absolute; inset: 0; z-index: 900;
        pointer-events: none;
    }
    #assoc-place-overlay.active { display: block; }
    #assoc-place-bar {
        position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
        z-index: 901; pointer-events: auto;
        background: rgba(26,26,46,0.95); border: 1px solid #f59e0b;
        border-radius: 1rem; padding: 0.5rem 1rem;
        display: flex; align-items: center; gap: 0.75rem;
        box-shadow: 0 4px 20px rgba(245,158,11,0.3);
    }
    #assoc-place-bar span { color: #f59e0b; font-size: 0.8rem; font-weight: 700; white-space: nowrap; }
    #assoc-place-cancel {
        background: #3b3b58; color: #e2e8f0; border: none; border-radius: 0.5rem;
        padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 600; cursor: pointer;
    }

    .assoc-field { margin-top: 0.75rem; }
    .assoc-field:first-child { margin-top: 0; }
    .assoc-label { display: block; font-size: 0.7rem; font-weight: 700; color: #9ca3af; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .assoc-known-btns { display: flex; gap: 0.5rem; }
    .assoc-known-btn {
        flex: 1; padding: 0.6rem; border-radius: 0.75rem; cursor: pointer;
        background: #2a2a3e; border: 1px solid #404060; color: #9ca3af;
        font-size: 0.82rem; font-weight: 700; touch-action: manipulation;
    }
    .assoc-known-btn.active[data-known="1"] { background: #22c55e; border-color: #22c55e; color: #1a1a2e; }
    .assoc-known-btn.active[data-known="0"] { background: #ef4444; border-color: #ef4444; color: #1a1a2e; }
    .assoc-delete-btn {
        margin-top: 0.5rem; width: 100%; padding: 0.6rem;
        background: transparent; border: 1px solid #f87171; color: #f87171;
        border-radius: 0.75rem; cursor: pointer; font-size: 0.82rem; font-weight: 600;
    }
    .assoc-delete-btn:active { opacity: 0.7; }
    #assoc-status { font-size: 0.75rem; margin-top: 0.5rem; text-align: center; color: #f87171; }
    .assoc-known-badge { font-size: 0.62rem; font-weight: 700; padding: 1px 6px; border-radius: 1rem; margin-left: 4px; white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <div class="flex-1 min-w-0">
            <div id="mode-switch" class="{{ session('api_user.is_admin') ? 'three-modes' : '' }}">
                <button type="button" class="mode-btn active" data-mode="findings">Znaleziska</button>
                <button type="button" class="mode-btn" data-mode="expeditions">Poszukiwania</button>
                @if(session('api_user.is_admin'))
                    <button type="button" class="mode-btn" data-mode="associations">Stowarzyszenia</button>
                @endif
            </div>
            @if($findingsCount !== null)
                <p id="findings-total" class="text-xs text-gray-400 mt-1">Łącznie dodano: {{ number_format($findingsCount, 0, ',', ' ') }}</p>
            @endif
        </div>
        <a id="header-add-btn" href="{{ route('findings.create') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 text-xl flex-shrink-0">➕</a>
    </div>

    {{-- Mapa --}}
    <div id="map-wrapper" class="flex-1">
        <div id="browse-map"></div>

        <div id="zoom-info">🗺️ Przybliż, aby zobaczyć szczegóły</div>

        <button id="locate-btn">🎯 Moja pozycja</button>
        <button id="layer-btn" onclick="toggleLayer()">🛰️ Ortofoto</button>

        <div id="exp-legend">
            <div><span class="legend-dot" style="background:#22c55e"></span>Publiczne</div>
            <div><span class="legend-dot" style="background:#eab308"></span>Uczestniczysz</div>
            <div><span class="legend-dot" style="background:#ef4444"></span>Twoje</div>
        </div>

        @if(session('api_user.is_admin'))
            <div id="assoc-legend">
                <div><span class="legend-dot" style="background:#22c55e"></span>Znamy się</div>
                <div><span class="legend-dot" style="background:#ef4444"></span>Nie znamy się</div>
            </div>

            {{-- Overlay wskazywania miejsca dla nowego stowarzyszenia --}}
            <div id="assoc-place-overlay">
                <div id="assoc-place-bar">
                    <span>📍 Kliknij miejsce stowarzyszenia</span>
                    <button id="assoc-place-cancel" onclick="cancelAssociationPlacement()">Anuluj</button>
                </div>
            </div>
        @endif

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
            <div id="modal-sheet-header">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div class="modal-title" id="modal-pin-location"></div>
                        <div class="modal-meta-row" id="modal-pin-finder"></div>
                    </div>
                    <button class="modal-close" onclick="closeModal()">✕</button>
                </div>
            </div>
            <div id="modal-sheet-body">
                <div class="modal-body">
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
    </div>

    {{-- Modal wiadomości do znalazcy --}}
    <div id="message-modal" onclick="handleMessageBackdrop(event)">
        <div id="message-sheet">
            <div id="message-sheet-header">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div class="modal-title" id="msg-modal-title">Napisz wiadomość</div>
                    <button class="modal-close" onclick="closeMessageModal()">✕</button>
                </div>
            </div>
            <div id="message-sheet-body">
                <div class="modal-body">
                    <textarea id="modal-msg" class="modal-textarea" rows="3" placeholder="Napisz wiadomość do znalazcy…"></textarea>
                    <button id="modal-send" class="modal-send-btn" onclick="sendModalMessage()">Wyślij wiadomość</button>
                    <div id="modal-status"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: prześlij znajomemu --}}
    <div id="share-modal" onclick="handleShareBackdrop(event)">
        <div id="share-sheet">
            <div id="share-sheet-header">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div class="modal-title">Prześlij znajomemu</div>
                    <button class="modal-close" onclick="closeShareSheet()">✕</button>
                </div>
            </div>
            <div id="share-sheet-body">
                <div class="modal-body">
                    <div class="relative" id="shareSearchWrap">
                        <input type="text" id="shareUserInput" class="modal-textarea" style="resize:none" placeholder="🔍 Wpisz nick znajomego lub „Czat”..." autocomplete="off">
                        <div id="shareAutocomplete" class="autocomplete-list" style="display:none"></div>
                    </div>
                    <div id="shareSelectedUser" style="display:none;align-items:center;gap:0.5rem;background:#2a2a3e;border-radius:0.75rem;padding:0.6rem 0.75rem;margin-top:0.6rem">
                        <div id="shareSelectedAvatar" style="width:28px;height:28px;border-radius:50%;background:#404060;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#e2e8f0;overflow:hidden;flex-shrink:0"></div>
                        <span id="shareSelectedName" style="font-size:0.85rem;font-weight:600;color:#fff;flex:1"></span>
                        <button id="shareClearUserBtn" style="background:none;border:none;color:#9ca3af;font-size:1.1rem;cursor:pointer;padding:0 4px">✕</button>
                    </div>
                    <textarea id="shareMessageBody" class="modal-textarea" rows="2" maxlength="2000" placeholder="Dodaj wiadomość (opcjonalnie)..." style="margin-top:0.6rem"></textarea>
                    <button id="shareSendBtn" class="modal-send-btn" disabled onclick="sendShareMessage()">Wyślij</button>
                    <div id="shareStatus"></div>
                </div>
            </div>
        </div>
    </div>

    @if(session('api_user.is_admin'))
        {{-- Modal stowarzyszenia (dodawanie / edycja) --}}
        <div id="assoc-modal" onclick="handleAssociationBackdrop(event)">
            <div id="assoc-sheet">
                <div id="assoc-sheet-header">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div class="modal-title" id="assoc-modal-title">Dodaj stowarzyszenie</div>
                        <button class="modal-close" onclick="closeAssociationModal()">✕</button>
                    </div>
                </div>
                <div id="assoc-sheet-body">
                    <div class="modal-body">
                        <div class="assoc-field">
                            <label class="assoc-label" for="assoc-name">Nazwa</label>
                            <input type="text" id="assoc-name" class="modal-textarea" maxlength="255" placeholder="Nazwa stowarzyszenia" autocomplete="off">
                        </div>
                        <div class="assoc-field">
                            <label class="assoc-label" for="assoc-phone">Numer kontaktowy</label>
                            <input type="tel" id="assoc-phone" class="modal-textarea" maxlength="50" placeholder="np. 600 100 200" autocomplete="off">
                        </div>
                        <div class="assoc-field">
                            <span class="assoc-label">Czy się znamy?</span>
                            <div class="assoc-known-btns">
                                <button type="button" class="assoc-known-btn" data-known="1" onclick="setAssociationKnown(true)">Tak</button>
                                <button type="button" class="assoc-known-btn active" data-known="0" onclick="setAssociationKnown(false)">Nie</button>
                            </div>
                        </div>
                        <button id="assoc-save" class="modal-send-btn" onclick="saveAssociation()">Zapisz</button>
                        <button id="assoc-delete" class="assoc-delete-btn" style="display:none" onclick="deleteAssociation()">🗑️ Usuń stowarzyszenie</button>
                        <div id="assoc-status"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif


</div>
@endsection

@push('scripts')
<script>
const CLUSTERS_URL  = "{{ route('findings.api') }}";
const EXPEDITIONS_MAP_URL = "{{ route('expeditions.map-areas') }}";
const EXPEDITION_CREATE_URL = "{{ route('expeditions.create') }}";
const FINDINGS_CREATE_URL   = "{{ route('findings.create') }}";
const PINS_API_BASE = "{{ url('/api/pins') }}";
const MESSAGE_BASE  = "{{ url('/api/findings') }}";
const CREATE_URL    = "{{ route('findings.create') }}";
const CSRF_TOKEN    = '{{ csrf_token() }}';
const USERS_SEARCH_URL = "{{ route('users.search') }}";
const ASSOCIATIONS_URL = "{{ url('/api/associations') }}";
const IS_ADMIN = @json((bool) session('api_user.is_admin'));

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

// 'findings' (klastry i pinezki), 'expeditions' (obszary poszukiwań)
// albo 'associations' (stowarzyszenia — wyłącznie dla administratorów)
let mapMode = 'findings';

const ROLE_COLORS = { owner: '#ef4444', member: '#eab308', public: '#22c55e' };
const ROLE_LABELS = { owner: 'Twoje poszukiwanie', member: 'Uczestniczysz', public: 'Publiczne' };

// Poniżej tego zoomu obszary są rysowane jako punkt — API nie wysyła wtedy geometrii.
const EXPEDITION_POLYGON_ZOOM = 10;

const PHASE_STYLES = {
    upcoming: { label: 'Nadchodzące', bg: 'rgba(96,165,250,0.15)', color: '#60a5fa' },
    active:   { label: 'W trakcie',   bg: 'rgba(52,211,153,0.15)', color: '#34d399' },
    finished: { label: 'Zakończone',  bg: 'rgba(148,163,184,0.15)', color: '#94a3b8' },
};

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

const expeditionLayer = L.layerGroup().addTo(map);
const associationLayer = L.layerGroup().addTo(map);

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
    const html = `<div class="cluster-bubble ${cls}" style="width:${size}px;height:${size}px"><span class="cb-count">${count}</span><span class="cb-label">${escHtml(shortName)}</span></div>`;
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
    loadTimer = setTimeout(() => {
        if (mapMode === 'expeditions') { fetchExpeditions(); }
        else if (mapMode === 'associations') { updateAssociationPanel(); }
        else { fetchClusters(); }
    }, 350);
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
        .then(data => {
            // Odpowiedź mogła dotrzeć po przełączeniu na tryb poszukiwań.
            if (mapMode !== 'findings') { return; }
            renderData(data, zoom);
        })
        .catch(() => {});
}

// --- Tryb poszukiwań ---

function fetchExpeditions() {
    const zoom = map.getZoom();
    const bounds = map.getBounds();

    const params = new URLSearchParams({
        zoom,
        sw_lat: bounds.getSouth().toFixed(6),
        sw_lng: bounds.getWest().toFixed(6),
        ne_lat: bounds.getNorth().toFixed(6),
        ne_lng: bounds.getEast().toFixed(6),
    });

    fetch(`${EXPEDITIONS_MAP_URL}?${params}`)
        .then(r => r.json())
        .then(res => {
            // Odpowiedź mogła dotrzeć po przełączeniu z powrotem na znaleziska.
            if (mapMode !== 'expeditions') { return; }
            renderExpeditions(res.data ?? [], zoom);
        })
        .catch(() => {});
}

function renderExpeditions(items, zoom) {
    expeditionLayer.clearLayers();

    items.forEach(item => {
        const color = ROLE_COLORS[item.role] ?? ROLE_COLORS.public;
        const layer = (zoom >= EXPEDITION_POLYGON_ZOOM && item.area)
            ? L.geoJSON(item.area, {
                style: { color, weight: 2, fillColor: color, fillOpacity: 0.2 },
            })
            : L.circleMarker([item.center_lat, item.center_lng], {
                radius: 7, color: '#1a1a2e', weight: 2,
                fillColor: color, fillOpacity: 0.9,
            });

        layer.bindTooltip(escHtml(item.name ?? 'Poszukiwanie'), { direction: 'top', opacity: 0.9 });
        layer.on('click', () => { window.location.href = `/expeditions/${item.id}`; });
        expeditionLayer.addLayer(layer);
    });

    updateExpeditionPanel(items);
}

function updateExpeditionPanel(items) {
    panelTotalCount = items.length;

    document.getElementById('panel-level').textContent = 'Poszukiwania';
    document.getElementById('toggle-count').textContent = items.length > 0 ? items.length : '—';
    document.getElementById('findings-count').textContent = items.length > 0
        ? `${items.length} ${expeditionNoun(items.length)} w widoku`
        : 'Brak poszukiwań w widoku';
    document.getElementById('panel-toggle').classList.toggle('has-findings', items.length > 0 && !panelOpen);

    const list = document.getElementById('findings-list');

    if (!items.length) {
        list.innerHTML = '<div id="empty-state">Brak poszukiwań w tym widoku</div>';
        return;
    }

    list.innerHTML = '';
    items.forEach(item => {
        const color = ROLE_COLORS[item.role] ?? ROLE_COLORS.public;
        const el = document.createElement('div');
        el.className = 'finding-item';
        el.innerHTML = `
            <div class="finding-item-name">
                <span class="exp-role-dot" style="background:${color}"></span>${escHtml(item.name ?? 'Poszukiwanie')}
                ${phaseBadge(item.phase)}
            </div>
            <div class="finding-item-meta">📅 ${escHtml(item.starts_at ?? '')} — ${escHtml(item.ends_at ?? '')}</div>
            <div class="finding-item-meta">${ROLE_LABELS[item.role] ?? ''} · 👥 ${item.participants_count ?? 0} · ⚒️ ${item.findings_count ?? 0}</div>
        `;
        el.addEventListener('click', () => {
            highlightItem(el);
            const b = item.bounds;
            if (b) {
                map.fitBounds([[b.min_lat, b.min_lng], [b.max_lat, b.max_lng]], { padding: [30, 30] });
            }
        });
        list.appendChild(el);
    });
}

function phaseBadge(phase) {
    const p = PHASE_STYLES[phase];
    if (!p) { return ''; }
    return `<span class="exp-phase-badge" style="background:${p.bg};color:${p.color}">● ${p.label}</span>`;
}

function expeditionNoun(count) {
    return count === 1 ? 'poszukiwanie' : 'poszukiwań';
}

// --- Tryb stowarzyszeń (tylko administratorzy) ---

let allAssociations   = [];
let associationDraft  = null;   // { latitude, longitude } dla nowej pinezki
let editedAssociation = null;   // edytowane stowarzyszenie albo null przy dodawaniu
let associationKnown  = false;

function assocPinIcon(isKnown) {
    return L.divIcon({
        html: `<div class="assoc-pin ${isKnown ? 'known' : 'unknown'}"></div>`,
        iconSize: [24, 24], iconAnchor: [12, 24], popupAnchor: [0, -26],
        className: '',
    });
}

function fetchAssociations() {
    fetch(ASSOCIATIONS_URL)
        .then(r => {
            if (!r.ok) { throw new Error(); }
            return r.json();
        })
        .then(res => {
            // Odpowiedź mogła dotrzeć po przełączeniu na inny tryb mapy.
            if (mapMode !== 'associations') { return; }
            allAssociations = res.data ?? [];
            renderAssociations();
        })
        .catch(() => {
            if (mapMode !== 'associations') { return; }
            document.getElementById('findings-list').innerHTML =
                '<div id="empty-state">Nie udało się wczytać stowarzyszeń.</div>';
        });
}

function renderAssociations() {
    associationLayer.clearLayers();

    allAssociations.forEach(item => {
        const marker = L.marker([item.latitude, item.longitude], { icon: assocPinIcon(item.is_known) });
        marker.bindPopup(associationPopupHtml(item), { className: 'popup-dark' });
        associationLayer.addLayer(marker);
    });

    updateAssociationPanel();
}

function associationPopupHtml(item) {
    const phone = item.phone
        ? `<div style="font-size:0.78rem;margin-top:3px">📞 <a href="tel:${escHtml(item.phone)}" style="color:#60a5fa">${escHtml(item.phone)}</a></div>`
        : '<div style="font-size:0.78rem;margin-top:3px;color:#9ca3af">📞 brak numeru</div>';

    return `
        <div style="font-weight:700;font-size:0.85rem">${escHtml(item.name)}</div>
        ${phone}
        <div style="font-size:0.78rem;margin-top:3px">
            ${item.is_known
                ? '<span style="color:#22c55e">🤝 Znamy się</span>'
                : '<span style="color:#ef4444">❔ Nie znamy się</span>'}
        </div>
        <button type="button" onclick="openAssociationModal(${item.id})"
            style="margin-top:0.5rem;width:100%;padding:0.4rem;border:1px solid #f59e0b;background:transparent;color:#f59e0b;border-radius:0.5rem;font-size:0.75rem;font-weight:700;cursor:pointer">
            ✏️ Edytuj
        </button>
    `;
}

/**
 * Panel listuje stowarzyszenia widoczne w bieżącym wycinku mapy — dane są
 * pobierane raz, więc przy przesuwaniu mapy filtrujemy je lokalnie.
 */
function updateAssociationPanel() {
    if (mapMode !== 'associations') { return; }

    const bounds = map.getBounds();
    const items = allAssociations.filter(item => bounds.contains([item.latitude, item.longitude]));

    panelTotalCount = items.length;

    document.getElementById('panel-level').textContent = 'Stowarzyszenia';
    document.getElementById('toggle-count').textContent = items.length > 0 ? items.length : '—';
    document.getElementById('findings-count').textContent = items.length > 0
        ? `${items.length} ${associationNoun(items.length)} w widoku`
        : 'Brak stowarzyszeń w widoku';
    document.getElementById('panel-toggle').classList.toggle('has-findings', items.length > 0 && !panelOpen);

    const list = document.getElementById('findings-list');

    if (!items.length) {
        list.innerHTML = allAssociations.length
            ? '<div id="empty-state">Brak stowarzyszeń w tym widoku</div>'
            : '<div id="empty-state">Nie dodano jeszcze żadnego stowarzyszenia.<br>Użyj przycisku ➕, aby dodać pierwsze.</div>';
        return;
    }

    list.innerHTML = '';
    items.forEach(item => {
        const color = item.is_known ? '#22c55e' : '#ef4444';
        const el = document.createElement('div');
        el.className = 'finding-item';
        el.innerHTML = `
            <div class="finding-item-name">
                <span class="exp-role-dot" style="background:${color}"></span>${escHtml(item.name)}
                <span class="assoc-known-badge" style="background:${color}22;color:${color}">
                    ${item.is_known ? 'Znamy się' : 'Nie znamy się'}
                </span>
            </div>
            <div class="finding-item-meta">📞 ${item.phone ? escHtml(item.phone) : 'brak numeru'}</div>
        `;
        el.addEventListener('click', () => {
            highlightItem(el);
            map.setView([item.latitude, item.longitude], Math.max(map.getZoom(), 12));
        });
        list.appendChild(el);
    });
}

function associationNoun(count) {
    return count === 1 ? 'stowarzyszenie' : 'stowarzyszeń';
}

function startAssociationPlacement() {
    closeAssociationModal();
    document.getElementById('assoc-place-overlay').classList.add('active');
    map.getContainer().style.cursor = 'crosshair';
    map.once('click', onAssociationPlaceClick);
}

function cancelAssociationPlacement() {
    const overlay = document.getElementById('assoc-place-overlay');
    if (!overlay) { return; }

    map.off('click', onAssociationPlaceClick);
    overlay.classList.remove('active');
    map.getContainer().style.cursor = '';
}

function onAssociationPlaceClick(e) {
    cancelAssociationPlacement();
    associationDraft = { latitude: e.latlng.lat, longitude: e.latlng.lng };
    openAssociationModal(null);
}

/**
 * Otwiera formularz stowarzyszenia — z podanym id w trybie edycji, bez id
 * w trybie dodawania nowej pinezki (współrzędne trzyma associationDraft).
 */
function openAssociationModal(associationId) {
    editedAssociation = associationId
        ? allAssociations.find(item => item.id === associationId) ?? null
        : null;

    if (associationId && !editedAssociation) { return; }

    map.closePopup();

    document.getElementById('assoc-modal-title').textContent =
        editedAssociation ? 'Edytuj stowarzyszenie' : 'Dodaj stowarzyszenie';
    document.getElementById('assoc-name').value = editedAssociation?.name ?? '';
    document.getElementById('assoc-phone').value = editedAssociation?.phone ?? '';
    document.getElementById('assoc-delete').style.display = editedAssociation ? '' : 'none';
    document.getElementById('assoc-status').textContent = '';
    document.getElementById('assoc-save').disabled = false;

    setAssociationKnown(editedAssociation?.is_known ?? false);

    document.getElementById('assoc-modal').classList.add('open');
}

function closeAssociationModal() {
    const modal = document.getElementById('assoc-modal');
    if (modal) { modal.classList.remove('open'); }
}

function handleAssociationBackdrop(e) {
    if (e.target === document.getElementById('assoc-modal')) { closeAssociationModal(); }
}

function setAssociationKnown(isKnown) {
    associationKnown = isKnown;
    document.querySelectorAll('.assoc-known-btn').forEach(btn => {
        btn.classList.toggle('active', (btn.dataset.known === '1') === isKnown);
    });
}

function saveAssociation() {
    const name = document.getElementById('assoc-name').value.trim();
    const status = document.getElementById('assoc-status');

    if (!name) {
        status.textContent = 'Podaj nazwę stowarzyszenia.';
        return;
    }

    const payload = {
        name,
        phone: document.getElementById('assoc-phone').value.trim() || null,
        is_known: associationKnown,
    };

    if (!editedAssociation) {
        if (!associationDraft) { return; }
        payload.latitude = associationDraft.latitude;
        payload.longitude = associationDraft.longitude;
    }

    const btn = document.getElementById('assoc-save');
    btn.disabled = true;
    btn.textContent = 'Zapisywanie…';
    status.textContent = '';

    fetch(editedAssociation ? `${ASSOCIATIONS_URL}/${editedAssociation.id}` : ASSOCIATIONS_URL, {
        method: editedAssociation ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(payload),
    })
    .then(r => {
        if (!r.ok) { throw new Error(); }
        return r.json();
    })
    .then(() => {
        associationDraft = null;
        closeAssociationModal();
        fetchAssociations();
    })
    .catch(() => {
        status.textContent = 'Nie udało się zapisać stowarzyszenia.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Zapisz';
    });
}

function deleteAssociation() {
    if (!editedAssociation) { return; }
    if (!confirm(`Usunąć stowarzyszenie „${editedAssociation.name}”?`)) { return; }

    const btn = document.getElementById('assoc-delete');
    btn.disabled = true;

    fetch(`${ASSOCIATIONS_URL}/${editedAssociation.id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    })
    .then(r => {
        if (!r.ok) { throw new Error(); }
        closeAssociationModal();
        fetchAssociations();
    })
    .catch(() => {
        document.getElementById('assoc-status').textContent = 'Nie udało się usunąć stowarzyszenia.';
    })
    .finally(() => {
        btn.disabled = false;
    });
}

if (IS_ADMIN) {
    // W trybie stowarzyszeń przycisk ➕ w nagłówku wskazuje miejsce na mapie
    // zamiast przechodzić do formularza znaleziska.
    document.getElementById('header-add-btn').addEventListener('click', e => {
        if (mapMode !== 'associations') { return; }
        e.preventDefault();
        startAssociationPlacement();
    });
}

function setMapMode(mode) {
    if (mode === mapMode) { return; }
    mapMode = mode;

    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.mode === mode);
    });

    const isExpeditions  = mode === 'expeditions';
    const isAssociations = mode === 'associations';
    const addBtn = document.getElementById('header-add-btn');
    const total  = document.getElementById('findings-total');

    addBtn.href = isExpeditions ? EXPEDITION_CREATE_URL : FINDINGS_CREATE_URL;
    if (total) { total.style.display = isExpeditions || isAssociations ? 'none' : ''; }
    document.getElementById('exp-legend').classList.toggle('visible', isExpeditions);
    document.getElementById('zoom-info').style.display = isExpeditions || isAssociations ? 'none' : '';

    const assocLegend = document.getElementById('assoc-legend');
    if (assocLegend) { assocLegend.classList.toggle('visible', isAssociations); }

    closeModal();
    cancelAssociationPlacement();
    clearTimeout(loadTimer);

    document.getElementById('findings-list').innerHTML = '<div id="empty-state">Ładowanie…</div>';

    if (isExpeditions) {
        clearMarkers();
        associationLayer.clearLayers();
        allPins = [];
        lastLevel = null;
        fetchExpeditions();
    } else if (isAssociations) {
        clearMarkers();
        expeditionLayer.clearLayers();
        allPins = [];
        lastLevel = null;
        fetchAssociations();
    } else {
        expeditionLayer.clearLayers();
        associationLayer.clearLayers();
        fetchClusters();
    }
}

document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => setMapMode(btn.dataset.mode));
});

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
                <div class="pin-finding-header">
                    <div class="flex-1 min-w-0">
                        <div class="pin-finding-name">🪙 ${escHtml(f.name)}</div>
                        <div class="pin-finding-depth">📏 ${f.depth_cm} cm głębokości</div>
                        <div class="pin-finding-meta">📅 ${f.found_at} · 👤 ${f.finder_id
                            ? `<a href="/users/${f.finder_id}" style="color:#f59e0b;font-weight:600;text-decoration:none;">${escHtml(f.finder ?? '')}</a>`
                            : escHtml(f.finder ?? '')
                        }</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.2rem;flex-shrink:0">
                        ${favoriteButtonHtml(f.id, f.is_favorited)}
                        ${likeButtonHtml(f.id, f.is_liked, f.likes_count)}
                    </div>
                </div>
                ${descHtml(f.description)}
                ${photosHtml(f)}
                <div class="pin-finding-actions">
                    <a href="/findings/${f.id}#comments" class="pin-msg-btn" style="margin:0;width:auto;flex:1;text-decoration:none;text-align:center">💬 Skomentuj</a>
                    <button class="pin-msg-btn" style="margin:0;width:auto;flex:1" onclick="openMessageModal(${f.id}, ${escHtml(JSON.stringify(f.name ?? ''))})">✉️ Wiadomość</button>
                    <button class="pin-msg-btn" style="margin:0;width:auto;flex:1" onclick="openShareSheet(${f.id}, ${escHtml(JSON.stringify(f.name ?? ''))})">📨 Prześlij</button>
                </div>
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
            if (typeof p === 'string') { return { thumb: p, full: p }; }
            // Prywatne zdjęcia idą przez własny proxy — URL API wymaga nagłówka Bearer, którego <img> nie wyśle.
            const full = p.is_private ? `/findings/${f.id}/photos/${p.id}` : p.url;
            const thumb = p.is_private ? `/findings/${f.id}/photos/${p.id}/thumb` : (p.thumb_url || p.url);
            return { thumb, full };
        })
        : (f.photo_url ? [{ thumb: f.photo_thumb_url || f.photo_url, full: f.photo_url }] : []);
    if (!photos.length) { return ''; }
    const imgs = photos.map(({ thumb, full }) => {
        const t = escHtml(thumb);
        return `<img class="pin-finding-photo" src="${t}" alt="" onclick="openLightbox(${escHtml(JSON.stringify(full ?? ''))})">`;
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
                        <div style="display:flex;align-items:center;gap:0.4rem;flex-shrink:0">
                            ${favoriteButtonHtml(f.id, f.is_favorited)}
                            ${likeButtonHtml(f.id, f.is_liked, f.likes_count)}
                            ${pin.is_mine ? `
                            <div class="finding-actions" style="margin-left:0">
                                <a href="/findings/${f.id}/edit" class="finding-action-btn edit" title="Edytuj">✏️</a>
                                <button class="finding-action-btn delete" onclick="deleteFinding(${f.id}, this)" title="Usuń">🗑️</button>
                            </div>` : ''}
                        </div>
                    </div>
                    ${descHtml(f.description)}
                    ${photosHtml(f)}
                    <div class="pin-finding-actions">
                        <a href="/findings/${f.id}#comments" class="pin-msg-btn" style="margin:0;width:auto;flex:1;text-decoration:none;text-align:center">💬 Skomentuj</a>
                        ${!pin.is_mine ? `<button class="pin-msg-btn" style="margin:0;width:auto;flex:1" onclick="openMessageModal(${f.id}, ${escHtml(JSON.stringify(f.name ?? ''))})">✉️ Wiadomość</button>` : ''}
                        <button class="pin-msg-btn" style="margin:0;width:auto;flex:1" onclick="openShareSheet(${f.id}, ${escHtml(JSON.stringify(f.name ?? ''))})">📨 Prześlij</button>
                    </div>
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

// --- Prześlij znajomemu ---
let activeShareFindingId = null;
let activeShareFindingName = '';
let shareSelectedUser = null;
let shareDeb = null;

function openShareSheet(findingId, findingName) {
    activeShareFindingId = findingId;
    activeShareFindingName = findingName;
    shareSelectedUser = null;

    document.getElementById('shareUserInput').value = '';
    document.getElementById('shareMessageBody').value = '';
    document.getElementById('shareAutocomplete').style.display = 'none';
    document.getElementById('shareSelectedUser').style.display = 'none';
    document.getElementById('shareStatus').textContent = '';
    document.getElementById('shareStatus').style.color = '';

    const sendBtn = document.getElementById('shareSendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = 'Wyślij';

    document.getElementById('share-modal').classList.add('open');
}

function closeShareSheet() {
    document.getElementById('share-modal').classList.remove('open');
    activeShareFindingId = null;
}

function handleShareBackdrop(e) {
    if (e.target === document.getElementById('share-modal')) { closeShareSheet(); }
}

function selectShareUser(u) {
    shareSelectedUser = u;
    document.getElementById('shareAutocomplete').style.display = 'none';
    document.getElementById('shareUserInput').value = '';

    const wrap = document.getElementById('shareSelectedUser');
    wrap.style.display = 'flex';
    document.getElementById('shareSelectedAvatar').innerHTML = u.isChat
        ? '💬'
        : (u.avatar_url
            ? `<img src="${escHtml(u.avatar_url)}" alt="" style="width:100%;height:100%;object-fit:cover">`
            : (u.name ? u.name.charAt(0).toUpperCase() : '?'));
    document.getElementById('shareSelectedName').textContent = u.name;
    document.getElementById('shareSendBtn').disabled = false;
}

document.getElementById('shareClearUserBtn').addEventListener('click', function () {
    shareSelectedUser = null;
    document.getElementById('shareSelectedUser').style.display = 'none';
    document.getElementById('shareSendBtn').disabled = true;
});

function renderShareAutocomplete(users, showChatOption) {
    const ac = document.getElementById('shareAutocomplete');
    ac.innerHTML = '';

    if (showChatOption) {
        const chatItem = document.createElement('div');
        chatItem.className = 'autocomplete-item';
        chatItem.textContent = '💬 Czat ogólny';
        chatItem.addEventListener('click', () => selectShareUser({ id: null, name: 'Czat ogólny', isChat: true }));
        ac.appendChild(chatItem);
    }

    users.forEach(u => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = u.name;
        item.addEventListener('click', () => selectShareUser(u));
        ac.appendChild(item);
    });

    if (!showChatOption && !users.length) { ac.style.display = 'none'; return; }
    ac.style.display = 'block';
}

document.getElementById('shareUserInput').addEventListener('input', function () {
    clearTimeout(shareDeb);
    const q = this.value.trim();
    const ac = document.getElementById('shareAutocomplete');
    if (q.length < 1) { ac.style.display = 'none'; return; }

    const showChatOption = 'czat'.startsWith(q.toLowerCase()) || 'chat'.startsWith(q.toLowerCase());

    if (q.length < 2) {
        if (showChatOption) { renderShareAutocomplete([], true); } else { ac.style.display = 'none'; }
        return;
    }

    shareDeb = setTimeout(() => {
        fetch(USERS_SEARCH_URL + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(users => renderShareAutocomplete(users, showChatOption))
            .catch(() => { ac.style.display = 'none'; });
    }, 300);
});

function sendShareMessage() {
    if (!shareSelectedUser || !activeShareFindingId) { return; }

    const bodyInput = document.getElementById('shareMessageBody');
    const status = document.getElementById('shareStatus');
    const btn = document.getElementById('shareSendBtn');
    const body = bodyInput.value.trim() || ('Polecam Ci to znalezisko: ' + activeShareFindingName);

    status.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Wysyłanie…';

    const payload = shareSelectedUser.isChat
        ? { body, chat: true }
        : { body, user_id: shareSelectedUser.id };

    fetch(`${MESSAGE_BASE}/${activeShareFindingId}/message`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok) {
            window.location.href = data.chat_message_id
                ? "{{ route('chat.show') }}"
                : "{{ url('/messages') }}/" + data.conversation_id;
        } else {
            status.textContent = data.message ?? 'Nie udało się wysłać.';
            status.style.color = '#f87171';
            btn.disabled = false;
            btn.textContent = 'Wyślij';
        }
    })
    .catch(() => {
        status.textContent = 'Błąd połączenia.';
        status.style.color = '#f87171';
        btn.disabled = false;
        btn.textContent = 'Wyślij';
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
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function toggleLike(findingId, btn) {
    const icon = btn.querySelector('.like-icon');
    const count = btn.querySelector('.like-count');
    fetch(`/api/findings/${findingId}/like`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        icon.textContent = data.is_liked ? '❤️' : '🤍';
        count.textContent = data.likes_count;
    })
    .catch(() => {});
}

function likeButtonHtml(findingId, isLiked, likesCount) {
    return `<button class="like-btn" onclick="event.stopPropagation(); toggleLike(${findingId}, this)"><span class="like-icon">${isLiked ? '❤️' : '🤍'}</span><span class="like-count">${likesCount || 0}</span></button>`;
}

function toggleFavorite(findingId, btn) {
    const icon = btn.querySelector('.favorite-icon');
    fetch(`/api/findings/${findingId}/favorite`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        icon.textContent = data.is_favorited ? '⭐' : '☆';
    })
    .catch(() => {});
}

function favoriteButtonHtml(findingId, isFavorited) {
    return `<button class="favorite-btn" title="Ulubione" onclick="event.stopPropagation(); toggleFavorite(${findingId}, this)"><span class="favorite-icon">${isFavorited ? '⭐' : '☆'}</span></button>`;
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
