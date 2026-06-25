@extends('layouts.app')
@section('title', 'Historius')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom">

    {{-- Header --}}
    <div class="px-6 pt-8 pb-4">
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
    <div class="mx-6 mb-4 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Actions --}}
    <div class="px-6 flex-1 flex flex-col gap-3">

        <a href="{{ route('findings.browse', ['user_id' => session('api_user.id')]) }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    ⚒️
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Twoje znaleziska</h3>
                    <p class="text-sm text-gray-400">Zobacz swoją kolekcję znalezisk</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.browse') }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    🔍
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Przeglądaj znaleziska</h3>
                    <p class="text-sm text-gray-400">Odkryj co znaleźli inni poszukiwacze</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.create') }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-green-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    ➕
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Dodaj znalezisko</h3>
                    <p class="text-sm text-gray-400">Zaznacz miejsce i opisz co znalazłeś</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

        <a href="{{ route('findings.map') }}" class="block">
            <div class="card flex items-center gap-4 active:scale-95 transition-transform">
                <div class="w-14 h-14 rounded-2xl bg-purple-500/20 flex items-center justify-center text-3xl flex-shrink-0">
                    🗺️
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-white text-lg">Przeglądaj mapę</h3>
                    <p class="text-sm text-gray-400">Znaleziska na interaktywnej mapie</p>
                </div>
                <span class="text-gray-500 text-xl">›</span>
            </div>
        </a>

    </div>


</div>
@endsection
