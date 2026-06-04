@extends('layouts.app')
@section('title', 'Rejestracja')

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
            <div class="text-6xl mb-4">⚒️</div>
            <h1 class="text-3xl font-bold text-white">Dołącz do nas</h1>
            <p class="text-gray-400 mt-2">Utwórz konto i zacznij dokumentować znaleziska</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
            @csrf

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Imię / Nick</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Twój nick"
                    class="input-field @error('name') border-red-500 @enderror"
                    autocomplete="name"
                >
                @error('name')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

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
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Hasło (min. 8 znaków)</label>
                <input
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    class="input-field @error('password') border-red-500 @enderror"
                    autocomplete="new-password"
                >
                @error('password')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Potwierdź hasło</label>
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    class="input-field"
                    autocomplete="new-password"
                >
            </div>

            {{-- Role --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5 ml-1">Kim jesteś?</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="role" value="poszukiwacz" class="sr-only peer"
                            {{ old('role', 'poszukiwacz') === 'poszukiwacz' ? 'checked' : '' }}>
                        <div class="peer-checked:border-amber-400 peer-checked:bg-amber-400/10 border-2 border-surface-card rounded-xl p-3 text-center transition-colors">
                            <div class="text-2xl mb-1">🔍</div>
                            <div class="text-sm font-semibold text-white">Poszukiwacz</div>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="role" value="naukowiec" class="sr-only peer"
                            {{ old('role') === 'naukowiec' ? 'checked' : '' }}>
                        <div class="peer-checked:border-amber-400 peer-checked:bg-amber-400/10 border-2 border-surface-card rounded-xl p-3 text-center transition-colors">
                            <div class="text-2xl mb-1">🔬</div>
                            <div class="text-sm font-semibold text-white">Naukowiec</div>
                        </div>
                    </label>
                </div>
                @error('role')
                    <p class="text-red-400 text-sm mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary mt-2">
                Zarejestruj się
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-400 text-sm">Masz już konto?
                <a href="{{ route('login') }}" class="text-amber-400 font-semibold">Zaloguj się</a>
            </p>
        </div>

    </div>
</div>
@endsection
