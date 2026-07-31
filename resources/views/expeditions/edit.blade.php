@extends('layouts.app')
@section('title', 'Edytuj poszukiwanie')

@include('expeditions.partials.area-editor-styles')

@php
    $statuses = [
        'draft' => ['Szkic', 'Widoczne tylko dla Ciebie — nie pojawia się w „Odkrywaj".'],
        'published' => ['Opublikowane', 'Aktywne poszukiwanie.'],
        'finished' => ['Zakończone', 'Archiwalne — uczestnicy nie przypinają już znalezisk.'],
        'cancelled' => ['Anulowane', 'Odwołane; znika z mapy poszukiwań.'],
    ];
@endphp

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- === Map screen (opened on demand from the details screen) === --}}
    @include('expeditions.partials.area-editor-step', [
        'backHref' => null,
        'active' => false,
        'title' => 'Zmień teren',
        'subtitle' => 'Dotknij obszaru, aby go usunąć — lub narysuj nowy',
        'nextLabel' => 'Zapisz teren →',
    ])

    {{-- === Details === --}}
    <div class="step-screen active" id="step2">
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <a href="{{ route('expeditions.show', $expedition['id']) }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</a>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold text-white truncate">Edytuj poszukiwanie</h1>
                <p class="text-xs text-gray-500 truncate">{{ $expedition['name'] ?? '' }}</p>
            </div>
        </div>

        <div class="screen-scroll px-5 py-5">
            <form id="expeditionForm" class="flex flex-col gap-5">
                <input type="hidden" name="area" id="area" value="">

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🧭 Nazwa <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ $expedition['name'] ?? '' }}" placeholder="np. Rajd nad Wisłą" class="input-field">
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Od <span class="text-red-400">*</span></label>
                        <input type="date" name="starts_at" value="{{ $expedition['starts_at'] ?? '' }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Do <span class="text-red-400">*</span></label>
                        <input type="date" name="ends_at" value="{{ $expedition['ends_at'] ?? '' }}" class="input-field">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📝 Opis <span class="text-gray-500 font-normal">(opcjonalny)</span></label>
                    <textarea name="description" rows="3" placeholder="Zasady, punkt zbiórki, pozwolenie WKZ..." class="input-field resize-none">{{ $expedition['description'] ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🗺️ Teren</label>
                    <button type="button" id="editAreaBtn" class="btn-secondary text-sm w-full" style="padding:0.7rem">✏️ Zmień teren na mapie</button>
                    <p class="text-xs text-gray-500 mt-1.5 ml-1" id="areaHint">Teren pozostanie bez zmian, dopóki go nie edytujesz.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">👁️ Widoczność</label>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="private" class="w-5 h-5 accent-amber-500" {{ ($expedition['visibility'] ?? 'private') === 'private' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🔒 Prywatne</span>
                                <span class="block text-xs text-gray-500">Tylko przez zaproszenie lub kod. Nie pojawia się w „Odkrywaj".</span>
                            </span>
                        </label>
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="public" class="w-5 h-5 accent-amber-500" {{ ($expedition['visibility'] ?? '') === 'public' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🌍 Publiczne</span>
                                <span class="block text-xs text-gray-500">Widoczne w „Odkrywaj"; inni mogą prosić o dołączenie.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🚦 Status</label>
                    <div class="flex flex-col gap-2">
                        @foreach($statuses as $value => [$label, $hint])
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="status" value="{{ $value }}" class="w-5 h-5 accent-amber-500" {{ ($expedition['status'] ?? 'draft') === $value ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">{{ $label }}</span>
                                <span class="block text-xs text-gray-500">{{ $hint }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">Zapisz zmiany</button>
            </form>
            <div class="h-8"></div>
        </div>
    </div>

</div>

@include('expeditions.partials.area-editor-overlay')

<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p class="mt-4 text-sm text-gray-300">Zapisywanie…</p>
</div>
@endsection

@push('scripts')
@include('expeditions.partials.area-editor-scripts')
<script>
    const EXP = @json($expedition);
    const UPDATE_URL = "{{ route('expeditions.update', $expedition['id']) }}";
    const SHOW_URL = "{{ route('expeditions.show', $expedition['id']) }}";
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // Pre-draw the saved area so the map screen opens on the existing polygons.
    AreaEditor.load(EXP.area);

    const areaHint = document.getElementById('areaHint');

    // The map is built inside a hidden screen, so the first fit has to wait
    // until it is actually sized on screen.
    let areaScreenOpened = false;
    document.getElementById('editAreaBtn').addEventListener('click', () => {
        AreaEditor.showStep(1);
        if (!areaScreenOpened) {
            areaScreenOpened = true;
            setTimeout(() => AreaEditor.fit(), 80);
        }
    });
    document.getElementById('areaBackBtn').addEventListener('click', () => AreaEditor.showStep(2));

    document.addEventListener('area:confirmed', () => {
        areaHint.textContent = '✓ Nowy teren zostanie zapisany razem ze zmianami.';
        areaHint.className = 'text-xs text-green-400 mt-1.5 ml-1';
        AreaEditor.showStep(2);
    });

    document.getElementById('expeditionForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const form = new FormData(this);
        const payload = {
            name: (form.get('name') || '').trim(),
            description: (form.get('description') || '').trim() || null,
            starts_at: form.get('starts_at'),
            ends_at: form.get('ends_at'),
            visibility: form.get('visibility'),
            status: form.get('status'),
        };

        if (!payload.name) { alert('Podaj nazwę poszukiwania.'); return; }
        if (!payload.starts_at || !payload.ends_at) { alert('Podaj daty poszukiwania.'); return; }
        if (payload.ends_at < payload.starts_at) { alert('Data zakończenia nie może być wcześniejsza niż rozpoczęcia.'); return; }

        // Only sent when the user actually redrew the area — the API keeps the
        // stored polygon when the field is absent.
        const area = document.getElementById('area').value;
        if (area) { payload.area = area; }

        const overlay = document.getElementById('loadingOverlay');
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        overlay.classList.remove('hidden');

        const reset = () => {
            overlay.classList.add('hidden');
            btn.disabled = false;
        };

        fetch(UPDATE_URL, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(async r => {
            const text = await r.text();
            let data = null;
            try { data = text ? JSON.parse(text) : null; } catch (_) { /* not JSON */ }
            return { status: r.status, ok: r.ok, data };
        })
        .then(({ status, ok, data }) => {
            if (ok) { window.location.href = SHOW_URL; return; }
            reset();

            if (data && data.errors) {
                alert(Object.values(data.errors).flat().join('\n'));
                return;
            }
            if (data && data.message) {
                alert(data.message);
                return;
            }
            if (status === 419) {
                alert('Sesja wygasła. Odśwież stronę i spróbuj ponownie.');
                return;
            }
            alert('Wystąpił błąd (' + status + '). Spróbuj ponownie.');
        })
        .catch(() => {
            reset();
            alert('Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.');
        });
    });
</script>
@endpush
