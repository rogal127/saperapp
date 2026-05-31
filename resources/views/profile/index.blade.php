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

        {{-- Avatar --}}
        <div class="flex flex-col items-center mb-8">
            <div class="relative">
                @if(!empty($user['avatar_url']))
                    <img src="{{ $user['avatar_url'] }}" alt="Avatar" class="avatar-ring">
                @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($user['full_name'] ?? '?', 0, 1)) }}
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
                        value="{{ old('name', $user['full_name'] ?? '') }}"
                        class="input-field" placeholder="Twój nick">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Email</label>
                    <input type="email" value="{{ $user['email'] ?? '' }}"
                        class="input-field opacity-50" disabled>
                </div>
                <button type="submit" class="btn-primary mt-1">Zapisz zmiany</button>
            </div>
        </form>

        {{-- Wylogowanie --}}
        <div class="mt-4 mb-6">
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
    fetch("{{ route('messages.unread') }}")
        .then(r => r.json())
        .then(data => {
            if (data.count > 0) {
                const link = document.getElementById('nav-messages');
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
