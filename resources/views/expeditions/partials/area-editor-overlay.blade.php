{{-- Google My Maps import sheet; driven by area-editor-scripts. --}}
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
