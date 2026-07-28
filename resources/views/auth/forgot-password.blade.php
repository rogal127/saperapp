@extends('layouts.app')
@section('title', 'Nie pamiętam hasła')
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
            <div class="text-6xl mb-4">🔑</div>
            <h1 class="text-3xl font-bold text-white">Nie pamiętasz hasła?</h1>
            <p class="text-gray-400 mt-2">Podaj swój adres e-mail, a wyślemy Ci link do ustawienia nowego hasła.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3">
                <p class="text-emerald-300 text-sm">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
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

            <button type="submit" class="btn-primary mt-2">
                Wyślij link
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-400 text-sm">Pamiętasz hasło?
                <a href="{{ route('login') }}" class="text-amber-400 font-semibold">Zaloguj się</a>
            </p>
        </div>

    </div>
</div>
@endsection
