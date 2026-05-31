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
            <img src="{{ $finding['photo_url'] }}" alt="" style="width:100%;max-height:55vh;object-fit:contain">
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
