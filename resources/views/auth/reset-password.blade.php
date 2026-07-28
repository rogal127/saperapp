@extends('layouts.app')
@section('title', 'Nowe hasło')
@section('hideNav', true)

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom overflow-y-auto">

    {{-- Back button --}}
    <div class="px-4 pt-4">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-gray-400 text-sm py-2 px-1">
            ‹ Wróć do logowania
        </a>
    </div>

    {{-- Content --}}
    <div class="flex-1 flex flex-col justify-center px-6 pb-8">

        <div class="mb-8 text-center">
            <div class="text-6xl mb-4">🔒</div>
            <h1 class="text-3xl font-bold text-white">Ustaw nowe hasło</h1>
            <p class="text-gray-400 mt-2">Wpisz nowe hasło do swojego konta</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    placeholder="twoj@email.pl"
                    class="input-field @error('email') border-red-500 @enderror"
                    autocomplete="email"
                    inputmode="email"
                >
                @error('email')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Nowe hasło</label>
                <input
                    type="password"
                    name="password"
                    placeholder="min. 8 znaków"
                    class="input-field @error('password') border-red-500 @enderror"
                    autocomplete="new-password"
                >
                @error('password')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password confirmation --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Powtórz hasło</label>
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    class="input-field"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-primary mt-2">
                Zmień hasło
            </button>
        </form>

    </div>
</div>
@endsection
