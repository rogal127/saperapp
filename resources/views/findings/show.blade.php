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

        {{-- Zdjęcie --}}
        @if(!empty($finding['photo_url']))
        <div style="background:#0d0d1a">
            <img src="{{ $finding['photo_url'] }}" alt="" style="width:100%;max-height:55vh;object-fit:contain;cursor:pointer" onclick="openLightbox(this.src)">
        </div>
        @endif

        <div class="px-4 py-5 flex flex-col gap-3">

            {{-- Nazwa --}}
            <h2 class="text-xl font-bold text-white">{{ $finding['name'] }}</h2>

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
            <span class="nav-icon">➕</span><span>Dodaj</span>
        </a>
        <a href="{{ route('messages.index') }}" class="nav-item">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </a>
    </div>

</div>
@endsection
