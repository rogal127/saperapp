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
