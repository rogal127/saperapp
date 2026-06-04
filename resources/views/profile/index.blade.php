@extends('layouts.app')
@section('title', 'Profil')

@push('styles')
<style>
    .avatar-ring {
        width: 88px; height: 88px; border-radius: 50%;
        border: 3px solid #f59e0b;
        object-fit: cover;
    }
    .avatar-placeholder {
        width: 88px; height: 88px; border-radius: 50%;
        border: 3px solid #f59e0b;
        background: #323248;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #f59e0b; font-weight: 700;
    }
    .avatar-btn {
        position: absolute; bottom: 0; right: 0;
        width: 28px; height: 28px; border-radius: 50%;
        background: #f59e0b; color: #1a1a2e;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; border: 2px solid #1e1e2e;
        cursor: pointer;
    }
    .section-title {
        font-size: 0.7rem; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="px-6 pt-6 pb-4 flex-shrink-0">
        <h1 class="text-xl font-bold text-white">Profil</h1>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mx-6 mb-4 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mx-6 mb-4 px-4 py-3 bg-red-900/50 border border-red-700 rounded-xl text-red-300 text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    <div class="flex-1 overflow-y-auto px-6 pb-4">

        {{-- Avatar --}}
        <div class="flex flex-col items-center mb-8">
            <div class="relative">
                @if(!empty($user['avatar_url']))
                    <img src="{{ $user['avatar_url'] }}" alt="Avatar" class="avatar-ring">
                @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($user['name'] ?? '?', 0, 1)) }}
                    </div>
                @endif
                <label for="avatar-input" class="avatar-btn" title="Zmień zdjęcie">✎</label>
            </div>
            <div class="text-gray-400 text-xs mt-2">Dotknij ołówka, aby zmienić zdjęcie</div>
        </div>

        {{-- Ukryty upload --}}
        <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatar-form">
            @csrf
            <input id="avatar-input" type="file" name="avatar" accept="image/*" class="hidden"
                onchange="document.getElementById('avatar-form').submit()">
        </form>

        {{-- Formularz --}}
        <div class="section-title">Dane konta</div>
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Nazwa / Nick</label>
                    <input type="text" name="name"
                        value="{{ old('name', $user['name'] ?? '') }}"
                        class="input-field" placeholder="Twój nick">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Email</label>
                    <input type="email" value="{{ $user['email'] ?? '' }}"
                        class="input-field opacity-50" disabled>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Kim jesteś?</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="role" value="poszukiwacz" class="sr-only peer"
                                {{ old('role', $user['role'] ?? 'poszukiwacz') === 'poszukiwacz' ? 'checked' : '' }}>
                            <div class="peer-checked:border-amber-400 peer-checked:bg-amber-400/10 border-2 border-surface-card rounded-xl p-3 text-center transition-colors">
                                <div class="text-2xl mb-1">🔍</div>
                                <div class="text-xs font-semibold text-white">Poszukiwacz</div>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="role" value="naukowiec" class="sr-only peer"
                                {{ old('role', $user['role'] ?? '') === 'naukowiec' ? 'checked' : '' }}>
                            <div class="peer-checked:border-amber-400 peer-checked:bg-amber-400/10 border-2 border-surface-card rounded-xl p-3 text-center transition-colors">
                                <div class="text-2xl mb-1">🔬</div>
                                <div class="text-xs font-semibold text-white">Naukowiec</div>
                            </div>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-1">Zapisz zmiany</button>
            </div>
        </form>

        {{-- Zgody WKZ --}}
        <div class="mt-6" id="wkz-consents">
            <div class="section-title">Zgody WKZ</div>

            @if($errors->has('wkz_name'))
                <div class="mb-3 px-4 py-2 bg-red-900/50 border border-red-700 rounded-xl text-red-300 text-xs">
                    {{ $errors->first('wkz_name') }}
                </div>
            @endif

            <form action="{{ route('profile.wkz-consents.store') }}" method="POST" class="flex flex-col gap-2 mb-3">
                @csrf
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Nazwa zgody (np. nr decyzji, teren…)"
                    class="input-field"
                    required
                >
                <button type="submit" class="btn-primary">+ Dodaj</button>
            </form>

            @if(empty($wkzConsents))
                <p class="text-xs text-gray-500 text-center py-3">Brak dodanych zgód.</p>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($wkzConsents as $consent)
                        <div class="card flex items-center gap-3">
                            <span class="text-lg">📋</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $consent['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $consent['findings_count'] ?? 0 }} znalezisk</p>
                            </div>
                            <form action="{{ route('profile.wkz-consents.destroy', $consent['id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 text-lg leading-none px-1"
                                    onclick="return confirm('Usunąć zgodę? Znaleziska nie zostaną usunięte.')"
                                    title="Usuń">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Raporty WKZ --}}
        @if(!empty($wkzConsents))
        <div class="mt-6">
            <div class="section-title">Raporty WKZ</div>
            <div class="flex flex-col gap-2">
                @foreach($wkzConsents as $consent)
                    <div class="card flex flex-col gap-2">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">📄</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $consent['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $consent['findings_count'] ?? 0 }} znalezisk</p>
                            </div>
                        </div>
                        @if(($consent['findings_count'] ?? 0) > 0)
                            <a href="{{ route('profile.wkz-consents.report', $consent['id']) }}"
                               class="btn-primary text-sm text-center">
                                ⬇ Pobierz raport PDF
                            </a>
                        @else
                            <p class="text-xs text-gray-600">Brak znalezisk — dodaj znaleziska do tej zgody, aby wygenerować raport.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Wylogowanie --}}
        <div class="mt-6 mb-6">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-secondary">Wyloguj się</button>
            </form>
        </div>

    </div>

    {{-- Nawigacja --}}
    <div class="nav-bar safe-bottom">
        <span class="nav-item active">
            <span class="nav-icon">👤</span><span>Profil</span>
        </span>
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
