@extends('layouts.app')
@section('title', 'Logowanie')
@section('hideNav', true)

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom overflow-y-auto">

    {{-- Back button --}}
    <div class="px-4 pt-4">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-gray-400 text-sm py-2 px-1">
            ‹ Wróć
        </a>
    </div>

    {{-- Content --}}
    <div class="flex-1 flex flex-col justify-center px-6 pb-8">

        <div class="mb-8 text-center">
            <div class="text-6xl mb-4">🔍</div>
            <h1 class="text-3xl font-bold text-white">Witaj z powrotem</h1>
            <p class="text-gray-400 mt-2">Zaloguj się na swoje konto</p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3">
                <p class="text-emerald-300 text-sm">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
            @csrf

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
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
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Hasło</label>
                <input
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    class="input-field @error('password') border-red-500 @enderror"
                    autocomplete="current-password"
                >
                @error('password')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember --}}
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-3 px-1 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-5 h-5 rounded accent-amber-500">
                    <span class="text-sm text-gray-300">Pamiętaj mnie</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-amber-400 px-1">Nie pamiętam hasła</a>
            </div>

            <button type="submit" class="btn-primary mt-2">
                Zaloguj się
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-400 text-sm">Nie masz konta?
                <a href="{{ route('register') }}" class="text-amber-400 font-semibold">Zarejestruj się</a>
            </p>
        </div>

    </div>
</div>
@endsection
