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
    .stat-box { background: #2a2a3e; border-radius: 1rem; padding: 0.75rem 1rem; flex: 1; text-align: center; }
    .stat-value { font-size: 1.25rem; font-weight: 700; color: #f59e0b; }
    .stat-label { font-size: 0.65rem; color: #9ca3af; margin-top: 2px; }
    .section-title { font-size: 0.7rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; }
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

        {{-- Avatar + imię --}}
        <div class="flex flex-col items-center mb-6">
            <div class="relative">
                @if(!empty($user['avatar_url']))
                    <img src="{{ $user['avatar_url'] }}" alt="Avatar" class="avatar-ring">
                @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($user['first_name'] ?? '?', 0, 1)) }}
                    </div>
                @endif
                <label for="avatar-input" class="avatar-btn">✎</label>
            </div>
            <div class="mt-3 text-center">
                <div class="text-lg font-bold text-white">{{ $user['full_name'] ?? 'Nieznany użytkownik' }}</div>
                <div class="text-sm text-gray-400">{{ $user['email'] ?? '' }}</div>
                @if(!empty($user['location_region']))
                <div class="text-xs text-gray-500 mt-1">📍 {{ $user['location_region'] }}</div>
                @endif
            </div>
        </div>

        {{-- Ukryty upload avatara --}}
        <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatar-form">
            @csrf
            <input id="avatar-input" type="file" name="avatar" accept="image/*" class="hidden"
                onchange="document.getElementById('avatar-form').submit()">
        </form>

        {{-- Statystyki --}}
        @if(!empty($user['rating_count']) || isset($user['searches_conducted']))
        <div class="flex gap-3 mb-6">
            <div class="stat-box">
                <div class="stat-value">{{ $user['searches_conducted'] ?? 0 }}</div>
                <div class="stat-label">Poszukiwania</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ $user['searches_joined'] ?? 0 }}</div>
                <div class="stat-label">Dołączenia</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format((float)($user['rating'] ?? 0), 1) }}</div>
                <div class="stat-label">Ocena</div>
            </div>
        </div>
        @endif

        {{-- Formularz edycji --}}
        <div class="mb-4">
            <div class="section-title">Dane osobowe</div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-3">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-400 mb-1">Imię</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user['first_name'] ?? '') }}"
                                class="input-field" placeholder="Imię">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-400 mb-1">Nazwisko</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user['last_name'] ?? '') }}"
                                class="input-field" placeholder="Nazwisko">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Telefon</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user['phone'] ?? '') }}"
                            class="input-field" placeholder="+48 000 000 000">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Region</label>
                        <input type="text" name="location_region" value="{{ old('location_region', $user['location_region'] ?? '') }}"
                            class="input-field" placeholder="np. Mazowieckie">
                    </div>
                    <button type="submit" class="btn-primary mt-1">Zapisz zmiany</button>
                </div>
            </form>
        </div>

        {{-- Wylogowanie --}}
        <div class="mt-2 mb-6">
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

@push('scripts')
<script>
(function () {
    fetch('{{ route('messages.unread') }}')
        .then(r => r.json())
        .then(data => {
            if (data.count > 0) {
                const link = document.getElementById('nav-messages');
                const icon = link.querySelector('.nav-icon');
                icon.textContent = '💬';
                link.style.position = 'relative';
                const badge = document.createElement('span');
                badge.style.cssText = 'position:absolute;top:6px;right:calc(50% - 18px);background:#f59e0b;color:#1a1a2e;border-radius:999px;font-size:0.55rem;font-weight:700;padding:1px 5px;min-width:16px;text-align:center;';
                badge.textContent = data.count > 99 ? '99+' : data.count;
                link.appendChild(badge);
            }
        })
        .catch(() => {});
})();
</script>
@endpush
@endsection
