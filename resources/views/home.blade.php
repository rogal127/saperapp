@extends('layouts.app')
@section('title', 'SaperApp')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="px-6 pt-8 pb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">SaperApp</h1>
                <p class="text-sm text-gray-400 mt-0.5">Poszukiwacze skarbów</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-surface-card flex items-center justify-center text-2xl">
                🪙
            </div>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
    <div class="mx-6 mb-4 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Hero --}}
    <div class="mx-6 mb-6">
        <div class="card relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-yellow-400 to-purple-600 rounded-xl"></div>
            <div class="relative">
                <div class="text-4xl mb-3">⚒️</div>
                <h2 class="text-xl font-bold text-white mb-1">Twoje odkrycia</h2>
                <p class="text-sm text-gray-400">Dodawaj znaleziska i przeglądaj co znaleźli inni detektoryści w Twojej okolicy.</p>
            </div>
        </div>
    </div>

    {{-- Main actions --}}
    <div class="px-6 flex-1 flex flex-col gap-4">

        {{-- Add finding --}}
        <a href="{{ route('findings.create') }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    ➕
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Dodaj znalezisko</h3>
                    <p class="text-sm text-gray-400">Zaznacz miejsce i opisz co znalazłeś</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        {{-- Browse map --}}
        <a href="{{ route('findings.map') }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-purple-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    🗺️
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Przeglądaj mapę</h3>
                    <p class="text-sm text-gray-400">Filtruj znaleziska w promieniu</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

    </div>

    {{-- Bottom nav --}}
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
        <a href="{{ route('messages.index') }}" class="nav-item" id="nav-messages">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </a>
    </div>

</div>
@endsection
