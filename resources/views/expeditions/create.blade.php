@extends('layouts.app')
@section('title', 'Załóż poszukiwanie')

@include('expeditions.partials.area-editor-styles')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- === STEP 1: Draw polygon === --}}
    @include('expeditions.partials.area-editor-step', [
        'backHref' => route('expeditions.index'),
        'title' => 'Zaznacz teren',
        'subtitle' => 'Krok 1 z 2 — narysuj jeden lub więcej obszarów',
        'nextLabel' => 'Dalej — szczegóły →',
    ])

    {{-- === STEP 2: Details === --}}
    <div class="step-screen" id="step2">
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
            <button type="button" id="backBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl">‹</button>
            <div class="flex-1">
                <h1 class="text-lg font-bold text-white">Szczegóły poszukiwania</h1>
                <p class="text-xs text-gray-500">Krok 2 z 2</p>
            </div>
        </div>

        <div class="screen-scroll px-5 py-5">
            <form method="POST" action="{{ route('expeditions.store') }}" id="expeditionForm" class="flex flex-col gap-5">
                @csrf
                <input type="hidden" name="area" id="area" value="{{ old('area') }}">

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">🧭 Nazwa <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="np. Rajd nad Wisłą" class="input-field @error('name') border-red-500 @enderror">
                    @error('name')<p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Od <span class="text-red-400">*</span></label>
                        <input type="date" name="starts_at" value="{{ old('starts_at') }}" class="input-field @error('starts_at') border-red-500 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📅 Do <span class="text-red-400">*</span></label>
                        <input type="date" name="ends_at" value="{{ old('ends_at') }}" class="input-field @error('ends_at') border-red-500 @enderror">
                    </div>
                </div>
                @error('ends_at')<p class="text-red-400 text-sm -mt-3 ml-1">{{ $message }}</p>@enderror

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">📝 Opis <span class="text-gray-500 font-normal">(opcjonalny)</span></label>
                    <textarea name="description" rows="3" placeholder="Zasady, punkt zbiórki, pozwolenie WKZ..." class="input-field resize-none">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5 ml-1">👁️ Widoczność</label>
                    <div class="flex flex-col gap-2" id="visibilityGroup">
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="private" class="w-5 h-5 accent-amber-500" {{ old('visibility', 'private') === 'private' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🔒 Prywatne</span>
                                <span class="block text-xs text-gray-500">Tylko przez zaproszenie lub kod. Nie pojawia się w „Odkrywaj".</span>
                            </span>
                        </label>
                        <label class="flex items-center gap-3 card cursor-pointer">
                            <input type="radio" name="visibility" value="public" class="w-5 h-5 accent-amber-500" {{ old('visibility') === 'public' ? 'checked' : '' }}>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-200">🌍 Publiczne</span>
                                <span class="block text-xs text-gray-500">Widoczne w „Odkrywaj"; inni mogą prosić o dołączenie.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="flex items-center justify-between gap-3 card cursor-pointer">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-300">📣 Opublikuj od razu</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Inaczej zapisze się jako szkic (draft).</span>
                    </span>
                    <input type="checkbox" name="publish" value="1" class="w-5 h-5 accent-amber-500 shrink-0" {{ old('publish', true) ? 'checked' : '' }}>
                </label>

                <button type="submit" class="btn-primary" id="submitBtn">Utwórz poszukiwanie</button>
            </form>
            <div class="h-8"></div>
        </div>
    </div>

</div>

@include('expeditions.partials.area-editor-overlay')

<div id="loadingOverlay" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black/70 backdrop-blur-sm hidden">
    <div class="w-10 h-10 border-4 border-gray-600 border-t-amber-400 rounded-full animate-spin"></div>
    <p class="mt-4 text-sm text-gray-300">Tworzenie…</p>
</div>
@endsection

@push('scripts')
@include('expeditions.partials.area-editor-scripts')
<script>
    document.addEventListener('area:confirmed', () => AreaEditor.showStep(2));

    document.getElementById('backBtn').addEventListener('click', () => AreaEditor.showStep(1));

    document.getElementById('expeditionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!document.getElementById('area').value) {
            AreaEditor.showStep(1);
            alert('Najpierw zaznacz teren na mapie.');
            return;
        }
        const overlay = document.getElementById('loadingOverlay');
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        overlay.classList.remove('hidden');

        const resetForm = () => {
            overlay.classList.add('hidden');
            btn.disabled = false;
        };

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(async r => {
            // If the server answered with a redirect (successful creation via the
            // non-JSON branch), fetch transparently follows it to the show page.
            // That is a success signal — go there rather than treating it as error.
            if (r.redirected && r.url) {
                window.location.href = r.url;
                return { handled: true };
            }
            // Parse the body defensively: an error page (419/500/redirect to login)
            // may not be JSON, and blindly calling r.json() would throw and hide
            // the real reason behind a generic "connection error".
            const text = await r.text();
            let data = null;
            try { data = text ? JSON.parse(text) : null; } catch (_) { /* not JSON */ }
            return { status: r.status, ok: r.ok, data };
        })
        .then(({ handled, status, ok, data }) => {
            if (handled) { return; }
            if (ok && data && data.redirect) { window.location.href = data.redirect; return; }
            resetForm();

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
            resetForm();
            alert('Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.');
        });
    });
</script>
@endpush
