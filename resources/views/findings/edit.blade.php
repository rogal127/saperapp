@extends('layouts.app')
@section('title', 'Edytuj znalezisko')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">
            ‹
        </button>
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white">Edytuj znalezisko</h1>
        </div>
    </div>

    {{-- Scrollable form --}}
    <div class="flex-1 overflow-y-auto px-5 py-5">
        <form
            method="POST"
            action="{{ route('findings.update', $finding['id']) }}"
            enctype="multipart/form-data"
            id="editForm"
            class="flex flex-col gap-5"
        >
            @csrf
            @method('PUT')
            @if(!empty($redirectTo))
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            @endif

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🪙 Nazwa znaleziska <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $finding['name'] ?? '') }}"
                    placeholder="np. Moneta srebrna, Fibula, Pierścionek..."
                    class="input-field @error('name') border-red-500 @enderror"
                    required
                >
                @error('name')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Depth --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📏 Głębokość wykopu <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <input
                        type="number"
                        name="depth_cm"
                        value="{{ old('depth_cm', $finding['depth_cm'] ?? 0) }}"
                        placeholder="0"
                        min="0"
                        max="9999"
                        inputmode="numeric"
                        class="input-field @error('depth_cm') border-red-500 @enderror"
                        style="padding-right: 3.5rem"
                        required
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">cm</span>
                </div>
                @error('depth_cm')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📝 Opis <span class="text-gray-500 font-normal">(opcjonalny)</span>
                </label>
                <textarea
                    name="description"
                    placeholder="Opisz znalezisko, stan zachowania, kontekst odkrycia..."
                    rows="3"
                    class="input-field resize-none @error('description') border-red-500 @enderror"
                >{{ old('description', $finding['description'] ?? '') }}</textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Private notes --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🔐 Notatki prywatne <span class="text-gray-500 font-normal">(opcjonalne)</span>
                </label>
                <textarea
                    name="private_notes"
                    placeholder="Notatki widoczne tylko dla Ciebie — współrzędne, szczegóły znaleziska, plany..."
                    rows="3"
                    class="input-field resize-none @error('private_notes') border-red-500 @enderror"
                >{{ old('private_notes', $finding['private_notes'] ?? '') }}</textarea>
                @error('private_notes')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1 ml-1">Tylko Ty widzisz tę treść.</p>
            </div>

            {{-- Private finding (visibility) --}}
            <div>
                <label class="flex items-center justify-between gap-3 card cursor-pointer">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-300">🙈 Tylko do mojego wglądu</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Znalezisko będzie prywatne — nie zobaczy go nikt poza Tobą.</span>
                    </span>
                    <input type="checkbox" name="is_private" value="1" class="w-5 h-5 accent-amber-500 shrink-0" {{ old('is_private', ($finding['is_private'] ?? false)) ? 'checked' : '' }}>
                </label>
            </div>

            {{-- Finding type --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🏛️ Typ znaleziska <span class="text-gray-500 font-normal">(opcjonalny)</span>
                </label>
                @php $currentType = old('type', $finding['type'] ?? ''); @endphp
                <div class="flex flex-col gap-2" id="findingTypeGroup">
                    <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ $currentType === 'archaeological_monument' ? 'border-red-500 bg-red-500/10' : 'border-transparent' }}"
                        data-active-border="border-red-500" data-active-bg="bg-red-500/10">
                        <input type="radio" name="type" value="archaeological_monument" class="hidden" {{ $currentType === 'archaeological_monument' ? 'checked' : '' }}>
                        <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-red-500"></span>
                        <span class="text-sm font-medium text-gray-200">Zabytek archeologiczny</span>
                    </label>
                    <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ $currentType === 'monument' ? 'border-yellow-400 bg-yellow-400/10' : 'border-transparent' }}"
                        data-active-border="border-yellow-400" data-active-bg="bg-yellow-400/10">
                        <input type="radio" name="type" value="monument" class="hidden" {{ $currentType === 'monument' ? 'checked' : '' }}>
                        <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-yellow-400"></span>
                        <span class="text-sm font-medium text-gray-200">Zabytek</span>
                    </label>
                    <label class="finding-type-btn flex items-center gap-3 card cursor-pointer border-2 transition-all {{ $currentType === 'non_monument' ? 'border-green-500 bg-green-500/10' : 'border-transparent' }}"
                        data-active-border="border-green-500" data-active-bg="bg-green-500/10">
                        <input type="radio" name="type" value="non_monument" class="hidden" {{ $currentType === 'non_monument' ? 'checked' : '' }}>
                        <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 bg-green-500"></span>
                        <span class="text-sm font-medium text-gray-200">Przedmiot niezabytkowy</span>
                    </label>
                </div>
            </div>

            {{-- Category --}}
            @if(!empty($findingCategories))
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🏷️ Kategoria <span class="text-gray-500 font-normal">(opcjonalna)</span>
                </label>
                <select name="finding_category_id" class="input-field">
                    <option value="">— Bez kategorii —</option>
                    @foreach($findingCategories as $cat)
                        <option value="{{ $cat['id'] }}"
                            {{ old('finding_category_id', $finding['category']['id'] ?? '') == $cat['id'] ? 'selected' : '' }}>
                            {{ $cat['name'] }}
                        </option>
                        @foreach($cat['children'] ?? [] as $child)
                            <option value="{{ $child['id'] }}"
                                {{ old('finding_category_id', $finding['category']['id'] ?? '') == $child['id'] ? 'selected' : '' }}>
                                &nbsp;&nbsp;↳ {{ $child['name'] }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            @endif

            {{-- WKZ Consent --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📋 Zgoda WKZ <span class="text-gray-500 font-normal">(opcjonalna)</span>
                </label>
                @if(!empty($wkzConsents))
                    <select name="wkz_consent_id" class="input-field">
                        <option value="">— Nie przypisuj zgody —</option>
                        @foreach($wkzConsents as $consent)
                            <option value="{{ $consent['id'] }}"
                                {{ old('wkz_consent_id', $finding['wkz_consent_id'] ?? '') == $consent['id'] ? 'selected' : '' }}>
                                {{ $consent['name'] }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <p class="text-xs text-gray-500 ml-1">Nie masz jeszcze żadnych zgód. <a href="{{ route('profile.show') }}#wkz-consents" class="text-amber-400">Dodaj zgodę w profilu</a>.</p>
                @endif
            </div>

            {{-- Expedition (poszukiwanie) --}}
            <div id="expeditionSection" class="hidden">
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    🧭 Przypisz do poszukiwania <span class="text-gray-500 font-normal">(opcjonalne)</span>
                </label>
                <div id="expeditionBody"></div>
                <p class="text-xs text-amber-300/80 mt-1 ml-1">Kierownik poszukiwania zobaczy to znalezisko — także jeśli oznaczysz je jako prywatne.</p>
            </div>

            {{-- Photos --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📷 Zdjęcia <span class="text-gray-500 font-normal">(opcjonalne, do 8)</span>
                </label>

                @php $existingPhotos = $finding['photos'] ?? []; @endphp

                <div id="photoGallery" class="grid grid-cols-3 gap-2">
                    @foreach($existingPhotos as $photo)
                    @php $photoPrivate = ! empty($photo['is_private']); @endphp
                    <div class="existing-photo relative rounded-xl overflow-hidden border-2 aspect-square {{ $photoPrivate ? 'border-purple-500' : 'border-gray-600' }}"
                        data-photo-id="{{ $photo['id'] }}"
                        data-was-private="{{ $photoPrivate ? '1' : '0' }}"
                        data-is-private="{{ $photoPrivate ? '1' : '0' }}">
                        <img src="{{ $photoPrivate ? route('findings.photo', [$finding['id'], $photo['id']]) : $photo['url'] }}" alt="" class="w-full h-full object-cover">
                        <button type="button"
                            class="remove-existing absolute top-1 right-1 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center text-base leading-none">
                            ×
                        </button>
                        <button type="button"
                            class="toggle-private absolute top-1 left-1 rounded-full w-7 h-7 flex items-center justify-center text-sm leading-none {{ $photoPrivate ? 'bg-purple-500 text-white' : 'bg-black/60 text-white/70' }}">
                            {{ $photoPrivate ? '🔒' : '🔓' }}
                        </button>
                        <span class="private-badge {{ $photoPrivate ? '' : 'hidden' }} absolute bottom-1 right-1 bg-purple-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">🔒 Prywatne</span>
                        <span class="main-badge hidden absolute bottom-1 left-1 bg-amber-500 text-black text-[10px] font-bold px-1.5 py-0.5 rounded">Główne</span>
                    </div>
                    @endforeach

                    <label id="photoAddTile" class="flex flex-col items-center justify-center gap-1 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors aspect-square">
                        <span class="text-2xl">📷</span>
                        <span class="text-[10px] text-gray-400 text-center px-1">Dodaj zdjęcie</span>
                        <input type="file" name="photos[]" accept="image/*" multiple class="hidden" id="photoInput">
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1 ml-1" id="photoHint">Dotknij ×, aby usunąć zdjęcie. Pierwsze będzie głównym.</p>
                <p class="text-xs text-gray-500 mt-1 ml-1">Dotknij 🔒 na zdjęciu, aby ukryć je przed innymi (zobaczysz je tylko Ty).</p>

                <div id="deleteIdsContainer" class="hidden"></div>
                <div id="privacyIdsContainer" class="hidden"></div>
                <div id="photosPrivateContainer" class="hidden"></div>
            </div>

            {{-- Report (PDF) --}}
            @php $hasReport = ! empty($finding['report_url']); @endphp
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📄 Załącz sprawozdanie <span class="text-gray-500 font-normal">(opcjonalne, PDF)</span>
                </label>

                <input type="hidden" name="delete_report" id="deleteReportInput" value="0">

                {{-- Istniejące sprawozdanie --}}
                <div id="existingReport" class="card flex items-center gap-3 {{ $hasReport ? '' : 'hidden' }}">
                    <span class="text-xl">📄</span>
                    @if($hasReport)
                        <a href="{{ $finding['report_url'] }}" target="_blank" rel="noopener"
                           class="text-sm text-amber-400 font-semibold flex-1 min-w-0 truncate">Pobierz sprawozdanie</a>
                    @else
                        <span class="text-sm text-gray-400 flex-1 min-w-0 truncate">Sprawozdanie</span>
                    @endif
                    <button type="button" id="reportRemoveExisting" class="text-red-400 text-sm font-semibold whitespace-nowrap">Usuń</button>
                </div>

                {{-- Wybór pliku --}}
                <label id="reportTile" class="flex items-center gap-3 card border-2 border-dashed border-gray-600 cursor-pointer active:border-amber-500 transition-colors {{ $hasReport ? 'hidden' : '' }}">
                    <span class="text-2xl">📄</span>
                    <span id="reportTileText" class="text-sm text-gray-400 flex-1 min-w-0 truncate">Dotknij, aby wybrać plik PDF</span>
                    <input type="file" name="report" accept="application/pdf,.pdf" class="hidden" id="reportInput">
                </label>

                {{-- Nowo wybrany plik --}}
                <div id="reportSelected" class="hidden card mt-2 flex items-center gap-3">
                    <span class="text-xl">📄</span>
                    <span id="reportName" class="text-sm text-gray-200 flex-1 min-w-0 truncate"></span>
                    <button type="button" id="reportRemove" class="text-red-400 text-sm font-semibold whitespace-nowrap">Usuń</button>
                </div>

                <p class="text-xs text-gray-500 mt-1 ml-1">Sprawozdanie widzisz tylko Ty. Maks. 20 MB.</p>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-primary" id="submitBtn">
                Zapisz zmiany
            </button>

        </form>

        <div class="h-8"></div>
    </div>

</div>

{{-- Loading overlay --}}
<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div id="loadingSpinner" class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p id="loadingText" class="mt-4 text-sm text-gray-300">Przetwarzanie…</p>
    <div id="progressContainer" class="hidden w-56 mt-3">
        <div class="w-full bg-gray-700/50 rounded-full h-2.5 overflow-hidden">
            <div id="progressBar" class="bg-amber-500 h-full rounded-full transition-all duration-200 ease-out" style="width: 0%"></div>
        </div>
        <p id="progressText" class="text-xs text-gray-500 mt-1.5 text-center">0%</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Finding type selector
    document.querySelectorAll('#findingTypeGroup .finding-type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#findingTypeGroup .finding-type-btn').forEach(b => {
                b.classList.remove(b.dataset.activeBorder, b.dataset.activeBg);
                b.classList.add('border-transparent');
            });
            btn.classList.remove('border-transparent');
            btn.classList.add(btn.dataset.activeBorder, btn.dataset.activeBg);
        });
    });

    // Expedition assignment
    const CURRENT_EXPEDITION_ID = @json($finding['expedition_id'] ?? null);
    (function loadExpeditions() {
        const section = document.getElementById('expeditionSection');
        const body = document.getElementById('expeditionBody');
        fetch("{{ route('expeditions.api') }}?scope=mine")
            .then(r => r.json())
            .then(res => {
                let items = (res.data || []).filter(e => e.status === 'published' && e.phase === 'active');
                // Ensure the currently assigned expedition stays selectable even if it ended.
                if (CURRENT_EXPEDITION_ID && !items.some(e => e.id === CURRENT_EXPEDITION_ID)) {
                    const cur = (res.data || []).find(e => e.id === CURRENT_EXPEDITION_ID);
                    if (cur) { items = [cur, ...items]; }
                }
                if (!items.length && !CURRENT_EXPEDITION_ID) { return; }
                const select = document.createElement('select');
                select.name = 'expedition_id';
                select.className = 'input-field';
                const def = document.createElement('option');
                def.value = '';
                def.textContent = '— Nie przypisuj —';
                select.appendChild(def);
                items.forEach(e => {
                    const opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.name;
                    if (CURRENT_EXPEDITION_ID === e.id) { opt.selected = true; }
                    select.appendChild(opt);
                });
                body.appendChild(select);
                section.classList.remove('hidden');
            })
            .catch(() => {});
    })();

    const MAX_PHOTOS = 8;
    const photoInput = document.getElementById('photoInput');
    const photoGallery = document.getElementById('photoGallery');
    const photoAddTile = document.getElementById('photoAddTile');
    const photoHint = document.getElementById('photoHint');
    const deleteIdsContainer = document.getElementById('deleteIdsContainer');
    const privacyIdsContainer = document.getElementById('privacyIdsContainer');
    const photosPrivateContainer = document.getElementById('photosPrivateContainer');
    let selectedPhotos = [];  // newly added File[]
    let selectedPrivate = []; // bool[] — równolegle do selectedPhotos

    // Przebudowuje make_private/make_public dla istniejących zdjęć (tylko gdy zmienione względem stanu początkowego).
    function syncExistingPrivacy() {
        privacyIdsContainer.innerHTML = '';
        photoGallery.querySelectorAll('.existing-photo').forEach(el => {
            if (el.dataset.deleted === '1') { return; }
            const was = el.dataset.wasPrivate === '1';
            const now = el.dataset.isPrivate === '1';
            if (now === was) { return; }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = now ? 'make_private_photo_ids[]' : 'make_public_photo_ids[]';
            input.value = el.dataset.photoId;
            privacyIdsContainer.appendChild(input);
        });
    }

    // Flagi prywatności dla NOWO dodanych zdjęć (indeksy zgodne z kolejnością photos[]).
    function syncPrivateInputs() {
        photosPrivateContainer.innerHTML = '';
        selectedPrivate.forEach((isPrivate, index) => {
            if (!isPrivate) { return; }
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'photos_private[]';
            hidden.value = index;
            photosPrivateContainer.appendChild(hidden);
        });
    }

    // Przełącznik kłódki na istniejących zdjęciach
    photoGallery.querySelectorAll('.existing-photo .toggle-private').forEach(btn => {
        const el = btn.closest('.existing-photo');
        btn.addEventListener('click', () => {
            const now = el.dataset.isPrivate === '1';
            const next = !now;
            el.dataset.isPrivate = next ? '1' : '0';
            btn.textContent = next ? '🔒' : '🔓';
            btn.classList.toggle('bg-purple-500', next);
            btn.classList.toggle('text-white', next);
            btn.classList.toggle('bg-black/60', !next);
            btn.classList.toggle('text-white/70', !next);
            el.classList.toggle('border-purple-500', next);
            el.classList.toggle('border-gray-600', !next);
            el.querySelector('.private-badge').classList.toggle('hidden', !next);
            syncExistingPrivacy();
        });
    });

    function existingKeptCount() {
        return photoGallery.querySelectorAll('.existing-photo:not([data-deleted="1"])').length;
    }

    function refreshState() {
        // "Główne" badge on the first kept photo (existing first, then new).
        photoGallery.querySelectorAll('.main-badge').forEach(b => b.classList.add('hidden'));
        const firstKept = photoGallery.querySelector('.existing-photo:not([data-deleted="1"]) .main-badge, .photo-thumb .main-badge');
        if (firstKept) { firstKept.classList.remove('hidden'); }

        const total = existingKeptCount() + selectedPhotos.length;
        const full = total >= MAX_PHOTOS;
        photoAddTile.classList.toggle('hidden', full);
        photoHint.textContent = full
            ? 'Osiągnięto limit 8 zdjęć.'
            : 'Dotknij ×, aby usunąć zdjęcie. Pierwsze będzie głównym.';
    }

    // Existing photos: toggle delete / undo
    photoGallery.querySelectorAll('.existing-photo').forEach(el => {
        const id = el.dataset.photoId;
        const btn = el.querySelector('.remove-existing');
        btn.addEventListener('click', () => {
            const isDeleted = el.dataset.deleted === '1';
            if (isDeleted) {
                el.dataset.deleted = '';
                el.classList.remove('opacity-40', 'grayscale');
                btn.textContent = '×';
                const hidden = deleteIdsContainer.querySelector(`input[value="${id}"]`);
                if (hidden) { hidden.remove(); }
            } else {
                el.dataset.deleted = '1';
                el.classList.add('opacity-40', 'grayscale');
                btn.textContent = '↺';
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'delete_photo_ids[]';
                hidden.value = id;
                deleteIdsContainer.appendChild(hidden);
            }
            refreshState();
        });
    });

    function renderNewPhotos() {
        photoGallery.querySelectorAll('.photo-thumb').forEach(el => el.remove());
        selectedPhotos.forEach((file, index) => {
            const isPrivate = selectedPrivate[index];
            const wrap = document.createElement('div');
            wrap.className = 'photo-thumb relative rounded-xl overflow-hidden border-2 aspect-square ' + (isPrivate ? 'border-purple-500' : 'border-amber-500');
            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'absolute top-1 right-1 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center text-base leading-none';
            btn.textContent = '×';
            btn.addEventListener('click', () => {
                selectedPhotos.splice(index, 1);
                selectedPrivate.splice(index, 1);
                renderNewPhotos();
            });

            const lockBtn = document.createElement('button');
            lockBtn.type = 'button';
            lockBtn.className = 'absolute top-1 left-1 rounded-full w-7 h-7 flex items-center justify-center text-sm leading-none ' + (isPrivate ? 'bg-purple-500 text-white' : 'bg-black/60 text-white/70');
            lockBtn.textContent = isPrivate ? '🔒' : '🔓';
            lockBtn.addEventListener('click', () => {
                selectedPrivate[index] = !selectedPrivate[index];
                renderNewPhotos();
            });

            if (isPrivate) {
                const lockBadge = document.createElement('span');
                lockBadge.className = 'absolute bottom-1 right-1 bg-purple-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded';
                lockBadge.textContent = '🔒 Prywatne';
                wrap.appendChild(lockBadge);
            }
            const badge = document.createElement('span');
            badge.className = 'main-badge hidden absolute bottom-1 left-1 bg-amber-500 text-black text-[10px] font-bold px-1.5 py-0.5 rounded';
            badge.textContent = 'Główne';
            wrap.appendChild(img);
            wrap.appendChild(btn);
            wrap.appendChild(lockBtn);
            wrap.appendChild(badge);
            photoGallery.insertBefore(wrap, photoAddTile);
        });
        refreshState();
    }

    photoInput.addEventListener('change', function () {
        for (const file of this.files) {
            if (existingKeptCount() + selectedPhotos.length >= MAX_PHOTOS) { break; }
            selectedPhotos.push(file);
            selectedPrivate.push(false);
        }
        this.value = '';
        renderNewPhotos();
    });

    refreshState();

    // Report (PDF) picker
    const reportInput = document.getElementById('reportInput');
    const reportTile = document.getElementById('reportTile');
    const reportSelected = document.getElementById('reportSelected');
    const reportName = document.getElementById('reportName');
    const reportRemove = document.getElementById('reportRemove');
    const existingReport = document.getElementById('existingReport');
    const reportRemoveExisting = document.getElementById('reportRemoveExisting');
    const deleteReportInput = document.getElementById('deleteReportInput');

    reportInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { return; }
        reportName.textContent = file.name;
        reportSelected.classList.remove('hidden');
        reportTile.classList.add('hidden');
    });

    reportRemove.addEventListener('click', function () {
        reportInput.value = '';
        reportName.textContent = '';
        reportSelected.classList.add('hidden');
        reportTile.classList.remove('hidden');
    });

    if (reportRemoveExisting) {
        reportRemoveExisting.addEventListener('click', function () {
            deleteReportInput.value = '1';
            existingReport.classList.add('hidden');
            reportTile.classList.remove('hidden');
        });
    }

    function resizeImageFile(file, maxPx, quality) {
        return new Promise((resolve) => {
            const img = new Image();
            const url = URL.createObjectURL(file);
            img.onload = () => {
                URL.revokeObjectURL(url);
                let { width, height } = img;
                if (width > maxPx || height > maxPx) {
                    if (width >= height) { height = Math.round(height * maxPx / width); width = maxPx; }
                    else { width = Math.round(width * maxPx / height); height = maxPx; }
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    const resized = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
                    resolve(resized);
                }, 'image/jpeg', quality);
            };
            img.src = url;
        });
    }

    document.getElementById('editForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        btn.disabled = true;
        btn.classList.add('opacity-60');
        overlay.classList.remove('hidden');

        if (selectedPhotos.length > 0) {
            loadingText.textContent = 'Przetwarzanie zdjęć…';
            btn.textContent = 'Przetwarzanie zdjęć...';

            const dt = new DataTransfer();
            for (let i = 0; i < selectedPhotos.length; i++) {
                loadingText.textContent = 'Przetwarzanie zdjęcia ' + (i + 1) + ' z ' + selectedPhotos.length + '…';
                const resized = await resizeImageFile(selectedPhotos[i], 1920, 0.82);
                dt.items.add(resized);
            }
            photoInput.files = dt.files;
            syncPrivateInputs();
        }

        btn.textContent = 'Przesyłanie...';

        if (selectedPhotos.length > 0) {
            loadingText.textContent = 'Przesyłanie zdjęć…';
            loadingSpinner.classList.add('hidden');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
        } else {
            loadingText.textContent = 'Zapisywanie zmian…';
        }

        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function (ev) {
            if (!ev.lengthComputable) return;
            var pct = Math.round((ev.loaded / ev.total) * 100);
            progressBar.style.width = pct + '%';
            progressText.textContent = pct + '%';
            if (pct < 100) {
                loadingText.textContent = 'Przesyłanie zdjęć… ' + pct + '%';
            } else {
                loadingText.textContent = 'Zapisywanie na serwerze…';
                loadingSpinner.classList.remove('hidden');
                progressContainer.classList.add('hidden');
            }
        });

        function resetUI() {
            overlay.classList.add('hidden');
            btn.disabled = false;
            btn.textContent = 'Zapisz zmiany';
            btn.classList.remove('opacity-60');
            loadingSpinner.classList.remove('hidden');
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
        }

        xhr.addEventListener('load', function () {
            try {
                var data = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (data.errors) {
                    var msgs = Object.values(data.errors).flat();
                    alert(msgs.join('\n'));
                } else if (data.message) {
                    alert(data.message);
                } else {
                    alert('Wystąpił błąd. Spróbuj ponownie.');
                }
            } catch (err) {
                alert('Wystąpił błąd. Spróbuj ponownie.');
            }
            resetUI();
        });

        xhr.addEventListener('error', function () {
            alert('Błąd połączenia. Sprawdź internet i spróbuj ponownie.');
            resetUI();
        });

        xhr.open('POST', this.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });
</script>
@endpush
