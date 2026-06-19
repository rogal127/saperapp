@extends('layouts.app')
@section('title', 'Znalezisko dodane')

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom px-6">

    <div class="flex-1 flex flex-col items-center justify-center text-center">

        {{-- Success icon --}}
        <div class="w-20 h-20 rounded-full bg-green-500/15 border border-green-500/30 flex items-center justify-center text-4xl mb-5">
            ✅
        </div>

        <h1 class="text-2xl font-bold text-white mb-2">
            {{ session('success', 'Znalezisko poprawnie dodane!') }}
        </h1>

        @if($location)
            <p class="text-sm text-gray-400 mb-1">Dodano w lokalizacji:</p>
            <p class="text-base font-semibold text-amber-400 mb-6">📍 {{ $location }}</p>
        @else
            <p class="text-sm text-gray-400 mb-6">Twoje znalezisko zostało zapisane.</p>
        @endif

    </div>

    {{-- Actions --}}
    <div class="flex flex-col gap-3 pb-8">
        @if($pinId)
            <a href="{{ route('findings.create', ['pin_id' => $pinId]) }}" class="btn-primary text-center">
                ➕ Dodaj kolejne znalezisko w tej lokalizacji
            </a>
        @endif
        <a href="{{ route('findings.map') }}" class="btn-secondary text-center">
            🗺️ Zobacz na mapie
        </a>
        <a href="{{ route('home') }}" class="btn-secondary text-center">
            Wróć do strony głównej
        </a>
    </div>

</div>
@endsection
