@extends('layouts.app')
@section('title', 'Historius')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="px-6 pt-6 pb-3 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Historius</h1>
                <p class="text-sm text-gray-400 mt-0.5">Poszukiwacze skarbów</p>
            </div>
            <a href="{{ route('profile.show') }}" class="w-12 h-12 rounded-full bg-surface-card flex items-center justify-center text-xl border-2 border-transparent active:border-amber-500 transition-colors">
                👤
            </a>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
    <div class="mx-6 mb-3 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm flex-shrink-0">
        {{ session('success') }}
    </div>
    @endif

    {{-- Actions (auto-fit: cards share the available height, no scroll) --}}
    <div class="px-6 pb-4 flex-1 min-h-0 flex flex-col gap-2.5">

        <a href="{{ route('findings.browse', ['user_id' => session('api_user.id')]) }}" class="block flex-1 min-h-0">
            <div class="card h-full flex items-center gap-3 active:scale-95 transition-transform" style="padding:0.75rem 1rem">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 flex items-center justify-center text-2xl flex-shrink-0">
                    ⚒️
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-white text-base">Twoje znaleziska</h3>
                    <p class="text-xs text-gray-400 truncate">Zobacz swoją kolekcję znalezisk</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.browse') }}" class="block flex-1 min-h-0">
            <div class="card h-full flex items-center gap-3 active:scale-95 transition-transform" style="padding:0.75rem 1rem">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center text-2xl flex-shrink-0">
                    🔍
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-white text-base">Przeglądaj znaleziska</h3>
                    <p class="text-xs text-gray-400 truncate">Odkryj co znaleźli inni poszukiwacze</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.create') }}" class="block flex-1 min-h-0">
            <div class="card h-full flex items-center gap-3 active:scale-95 transition-transform" style="padding:0.75rem 1rem">
                <div class="w-12 h-12 rounded-2xl bg-green-500/20 flex items-center justify-center text-2xl flex-shrink-0">
                    ➕
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-white text-base">Dodaj znalezisko</h3>
                    <p class="text-xs text-gray-400 truncate">Zaznacz miejsce i opisz co znalazłeś</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.map') }}" class="block flex-1 min-h-0">
            <div class="card h-full flex items-center gap-3 active:scale-95 transition-transform" style="padding:0.75rem 1rem">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/20 flex items-center justify-center text-2xl flex-shrink-0">
                    🗺️
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-white text-base">Przeglądaj mapę</h3>
                    <p class="text-xs text-gray-400 truncate">Znaleziska na interaktywnej mapie</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('expeditions.index') }}" class="block flex-1 min-h-0">
            <div class="card h-full flex items-center gap-3 active:scale-95 transition-transform" style="padding:0.75rem 1rem">
                <div class="relative w-12 h-12 rounded-2xl bg-teal-500/20 flex items-center justify-center text-2xl flex-shrink-0">
                    🧭
                    <span id="expeditionBadge" class="hidden absolute -top-1.5 -right-1.5 bg-amber-500 text-black text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-white text-base">Poszukiwania</h3>
                    <p id="expeditionSubtitle" class="text-xs text-gray-400 truncate">Organizuj i dołączaj do wspólnych rajdów</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

    </div>

</div>

@push('scripts')
<script>
(function () {
    fetch("{{ route('expeditions.pending-count') }}")
        .then(r => r.json())
        .then(data => {
            const count = data.count || 0;
            if (count <= 0) { return; }
            const badge = document.getElementById('expeditionBadge');
            if (badge) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.classList.remove('hidden');
            }
            const subtitle = document.getElementById('expeditionSubtitle');
            if (subtitle) {
                subtitle.textContent = count === 1
                    ? 'Masz 1 nowe zaproszenie do poszukiwań'
                    : 'Masz ' + count + ' nowe zaproszenia do poszukiwań';
                subtitle.classList.remove('text-gray-400');
                subtitle.classList.add('text-amber-400', 'font-semibold');
            }
        })
        .catch(() => {});
})();
</script>
@endpush
@endsection
