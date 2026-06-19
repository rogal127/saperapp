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
                        value="{{ old('depth_cm', $finding['depth_cm'] ?? '') }}"
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
                    class="input-field resize-none"
                >{{ old('description', $finding['description'] ?? '') }}</textarea>
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
                    class="input-field resize-none"
                >{{ old('private_notes', $finding['private_notes'] ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1 ml-1">Tylko Ty widzisz tę treść.</p>
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
            @if(!empty($wkzConsents))
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📋 Zgoda WKZ <span class="text-gray-500 font-normal">(opcjonalna)</span>
                </label>
                <select name="wkz_consent_id" class="input-field">
                    <option value="">— Nie przypisuj zgody —</option>
                    @foreach($wkzConsents as $consent)
                        <option value="{{ $consent['id'] }}"
                            {{ old('wkz_consent_id', $finding['wkz_consent_id'] ?? '') == $consent['id'] ? 'selected' : '' }}>
                            {{ $consent['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Photos --}}
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">
                    📷 Zdjęcia <span class="text-gray-500 font-normal">(opcjonalne, do 8)</span>
                </label>

                @php $existingPhotos = $finding['photos'] ?? []; @endphp

                <div id="photoGallery" class="grid grid-cols-3 gap-2">
                    @foreach($existingPhotos as $photo)
                    <div class="existing-photo relative rounded-xl overflow-hidden border-2 border-gray-600 aspect-square" data-photo-id="{{ $photo['id'] }}">
                        <img src="{{ $photo['url'] }}" alt="" class="w-full h-full object-cover">
                        <button type="button"
                            class="remove-existing absolute top-1 right-1 bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center text-base leading-none">
                            ×
                        </button>
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

                <div id="deleteIdsContainer" class="hidden"></div>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-primary" id="submitBtn">
                Zapisz zmiany
            </button>

        </form>

        <div class="h-8"></div>
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

    const MAX_PHOTOS = 8;
    const photoInput = document.getElementById('photoInput');
    const photoGallery = document.getElementById('photoGallery');
    const photoAddTile = document.getElementById('photoAddTile');
    const photoHint = document.getElementById('photoHint');
    const deleteIdsContainer = document.getElementById('deleteIdsContainer');
    let selectedPhotos = []; // newly added File[]

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
            const wrap = document.createElement('div');
            wrap.className = 'photo-thumb relative rounded-xl overflow-hidden border-2 border-amber-500 aspect-square';
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
                renderNewPhotos();
            });
            const badge = document.createElement('span');
            badge.className = 'main-badge hidden absolute bottom-1 left-1 bg-amber-500 text-black text-[10px] font-bold px-1.5 py-0.5 rounded';
            badge.textContent = 'Główne';
            wrap.appendChild(img);
            wrap.appendChild(btn);
            wrap.appendChild(badge);
            photoGallery.insertBefore(wrap, photoAddTile);
        });
        refreshState();
    }

    photoInput.addEventListener('change', function () {
        for (const file of this.files) {
            if (existingKeptCount() + selectedPhotos.length >= MAX_PHOTOS) { break; }
            selectedPhotos.push(file);
        }
        this.value = '';
        renderNewPhotos();
    });

    refreshState();

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
        const btn  = document.getElementById('submitBtn');

        if (selectedPhotos.length > 0) {
            e.preventDefault();
            btn.disabled = true;
            btn.textContent = 'Przetwarzanie zdjęć...';
            btn.classList.add('opacity-60');

            const dt = new DataTransfer();
            for (const file of selectedPhotos) {
                const resized = await resizeImageFile(file, 1920, 0.82);
                dt.items.add(resized);
            }
            photoInput.files = dt.files;
        }

        btn.disabled = true;
        btn.textContent = 'Zapisywanie...';
        btn.classList.add('opacity-60');

        if (selectedPhotos.length > 0) { this.submit(); }
    });
</script>
@endpush
