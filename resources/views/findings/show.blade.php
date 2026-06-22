@extends('layouts.app')
@section('title', ($finding['name'] ?? 'Znalezisko') . ' – szczegóły')

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</button>
        <h1 class="text-lg font-bold text-white flex-1 truncate">Szczegóły znaleziska</h1>
    </div>

    <div class="flex-1 overflow-y-auto">

        {{-- Zdjęcia --}}
        @php
            $photos = $finding['photos'] ?? [];
            if (empty($photos) && !empty($finding['photo_url'])) {
                $photos = [['url' => $finding['photo_url']]];
            }
        @endphp
        @if(!empty($photos))
        <div style="background:#0d0d1a;position:relative">
            <div id="photoCarousel" style="display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none">
                @foreach($photos as $photo)
                @php
                    $photoPrivate = ! empty($photo['is_private']);
                    $photoSrc = ($photoPrivate && ! empty($photo['id']))
                        ? route('findings.photo', [$finding['id'], $photo['id']])
                        : $photo['url'];
                @endphp
                <div style="flex:0 0 100%;scroll-snap-align:center;position:relative">
                    <img src="{{ $photoSrc }}" alt=""
                         style="width:100%;max-height:55vh;object-fit:contain;cursor:pointer"
                         onclick="openLightbox(this.src)">
                    @if($photoPrivate)
                    <span style="position:absolute;bottom:10px;right:10px;background:#a855f7;color:#fff;font-size:0.7rem;font-weight:700;padding:0.15rem 0.5rem;border-radius:0.5rem">🔒 Prywatne</span>
                    @endif
                </div>
                @endforeach
            </div>
            @if(count($photos) > 1)
            <div id="photoCounter"
                 style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);color:#fff;font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:1rem">
                1 / {{ count($photos) }}
            </div>
            <div style="position:absolute;bottom:10px;left:0;right:0;display:flex;justify-content:center;gap:6px;pointer-events:none">
                @foreach($photos as $i => $photo)
                <span class="carousel-dot" data-index="{{ $i }}"
                      style="width:7px;height:7px;border-radius:50%;background:{{ $i === 0 ? '#f59e0b' : 'rgba(255,255,255,0.4)' }};transition:background 0.2s"></span>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <div class="px-4 py-5 flex flex-col gap-3">

            {{-- Nazwa --}}
            <h2 class="text-xl font-bold text-white">{{ $finding['name'] }}</h2>

            @if(!empty($finding['is_private']))
                <span class="inline-flex items-center gap-1 self-start px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 text-xs font-semibold">
                    🙈 Prywatne — widoczne tylko dla Ciebie
                </span>
            @endif

            {{-- Właściciel --}}
            @if(!empty($finding['finder']))
                @php $finder = $finding['finder']; @endphp
                <a href="{{ route('users.show', $finder['id']) }}"
                   class="flex items-center gap-2 text-sm text-amber-400 font-semibold">
                    <div class="w-7 h-7 rounded-full bg-surface-card flex items-center justify-center text-xs font-bold overflow-hidden flex-shrink-0">
                        @if(!empty($finder['avatar_url']))
                            <img src="{{ $finder['avatar_url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
                        @else
                            {{ strtoupper(substr($finder['name'] ?? '?', 0, 1)) }}
                        @endif
                    </div>
                    {{ $finder['name'] }}
                </a>
            @endif

            {{-- Type badge --}}
            @if(!empty($finding['type']))
            @php
                $typeBadge = match($finding['type']) {
                    'archaeological_monument' => ['label' => 'Zabytek archeologiczny', 'style' => 'background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3)'],
                    'monument'                => ['label' => 'Zabytek',                'style' => 'background:rgba(250,204,21,0.15);color:#fde047;border:1px solid rgba(250,204,21,0.3)'],
                    'non_monument'            => ['label' => 'Przedmiot niezabytkowy', 'style' => 'background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3)'],
                    default => null,
                };
            @endphp
            @if($typeBadge)
            <div style="display:inline-flex;align-items:center;gap:0.375rem;{{ $typeBadge['style'] }};border-radius:0.75rem;padding:0.3rem 0.75rem;font-size:0.75rem;font-weight:600;width:fit-content">
                <span style="width:8px;height:8px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                {{ $typeBadge['label'] }}
            </div>
            @endif
            @endif

            {{-- Meta --}}
            <div class="flex flex-col gap-1">
                <div class="text-sm text-gray-400">📏 {{ $finding['depth_cm'] }} cm głębokości</div>
                <div class="text-sm text-gray-400">📅 {{ $finding['found_at'] }}{{ !empty($finding['city']) ? ' · ' . $finding['city'] : '' }}</div>
                @if(!empty($finding['voivodeship']))
                    <div class="text-sm text-gray-400">🗺️ {{ $finding['voivodeship'] }}</div>
                @endif
            </div>

            {{-- Opis --}}
            @if(!empty($finding['description']))
            <div class="bg-surface-card rounded-xl p-4 text-sm text-gray-300 leading-relaxed">
                {{ $finding['description'] }}
            </div>
            @endif

            <div class="text-xs text-gray-600">📌 Dokładna lokalizacja znaleziska jest chroniona</div>

        </div>
    </div>

    {{-- Nawigacja --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('profile.show') }}" class="nav-item">
            <span class="nav-icon">👤</span><span>Profil</span>
        </a>
        <a href="{{ route('findings.map') }}" class="nav-item">
            <span class="nav-icon">🗺️</span><span>Mapa</span>
        </a>
        <a href="{{ route('findings.create') }}" class="nav-item">
            <span class="nav-icon" style="font-weight:900;color:#f59e0b;">+</span><span>Dodaj</span>
        </a>
        <a href="{{ route('messages.index') }}" class="nav-item">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
    (function () {
        const carousel = document.getElementById('photoCarousel');
        const counter = document.getElementById('photoCounter');
        if (!carousel || !counter) { return; }

        const dots = Array.from(document.querySelectorAll('.carousel-dot'));
        const total = carousel.children.length;

        carousel.addEventListener('scroll', () => {
            const index = Math.round(carousel.scrollLeft / carousel.clientWidth);
            counter.textContent = `${index + 1} / ${total}`;
            dots.forEach((dot, i) => {
                dot.style.background = i === index ? '#f59e0b' : 'rgba(255,255,255,0.4)';
            });
        }, { passive: true });
    })();
</script>
@endpush
