@extends('layouts.app')
@section('title', 'Użytkownicy')

@push('styles')
<style>
    .user-item {
        background: #2a2a3e; border-radius: 1.25rem; padding: 0.875rem 1rem;
        display: flex; gap: 0.875rem; align-items: center;
        border: 2px solid transparent; transition: border-color 0.15s;
    }
    .user-item:active { border-color: #f59e0b; }
    .user-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: #323248; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.1rem; color: #f59e0b;
        overflow: hidden;
    }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .user-name { font-weight: 700; font-size: 0.9rem; color: #fff; }
    .alphabet-strip { display: flex; gap: 0.375rem; overflow-x: auto; scrollbar-width: none; }
    .alphabet-strip::-webkit-scrollbar { display: none; }
    .alphabet-btn {
        flex-shrink: 0; min-width: 2rem; height: 2rem; padding: 0 0.5rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.6rem; background: #2a2a3e; color: #e2e8f0;
        font-size: 0.8rem; font-weight: 700; text-decoration: none;
    }
    .alphabet-btn.active { background: #f59e0b; color: #1a1a2e; }
    .alphabet-btn.disabled { opacity: 0.3; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 flex-shrink-0">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</button>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-white truncate">Użytkownicy</h1>
            <p class="text-xs text-gray-400">{{ $totalUsers }} {{ $totalUsers === 1 ? 'użytkownik' : 'użytkowników' }}</p>
        </div>
    </div>

    {{-- Wyszukiwarka --}}
    <div class="px-4 pb-3 flex-shrink-0">
        <form method="GET" action="{{ route('users.index') }}">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Szukaj po imieniu i nazwisku…"
                class="input-field"
                oninput="clearTimeout(window.__usersSearchTimer); window.__usersSearchTimer = setTimeout(() => this.form.submit(), 400)"
            >
        </form>
    </div>

    {{-- Pasek alfabetu --}}
    @php
        $alphabet = ['A','Ą','B','C','Ć','D','E','Ę','F','G','H','I','J','K','L','Ł','M','N','Ń','O','Ó','P','Q','R','S','Ś','T','U','V','W','X','Y','Z','Ź','Ż'];
    @endphp
    <div class="px-4 pb-3 flex-shrink-0">
        <div class="alphabet-strip">
            <a href="{{ route('users.index') }}" class="alphabet-btn {{ $letter === '' ? 'active' : '' }}">Wszyscy</a>
            @foreach($alphabet as $l)
                @if(in_array($l, $availableLetters, true))
                    <a href="{{ route('users.index', ['letter' => $l]) }}" class="alphabet-btn {{ $letter === $l ? 'active' : '' }}">{{ $l }}</a>
                @else
                    <span class="alphabet-btn disabled">{{ $l }}</span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Lista użytkowników --}}
    <div class="flex-1 overflow-y-auto px-4 pb-4">
        @if(count($users) === 0)
            <div class="text-center py-16 text-gray-500">
                <div class="text-4xl mb-3">🔍</div>
                <div class="font-semibold text-gray-400">Nie znaleziono użytkowników</div>
            </div>
        @else
            <div class="flex flex-col gap-2.5">
                @foreach($users as $user)
                @php $initials = strtoupper(substr($user['name'] ?? '?', 0, 1)); @endphp
                <a href="{{ route('users.show', $user['id']) }}" class="user-item">
                    <div class="user-avatar">
                        @if(!empty($user['avatar_url']))
                            <img src="{{ $user['avatar_thumb_url'] ?? $user['avatar_url'] }}" alt="">
                        @else
                            {{ $initials }}
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="user-name truncate">{{ $user['name'] ?? 'Użytkownik' }}</div>
                    </div>
                    <span class="text-gray-500 text-xl">›</span>
                </a>
                @endforeach
            </div>

            @if($lastPage > 1)
            @php
                $baseParams = array_filter(['q' => $query, 'letter' => $letter], fn ($value) => $value !== '');
            @endphp
            <div class="flex items-center justify-between gap-3 mt-4">
                <a
                    href="{{ route('users.index', $baseParams + ['page' => max(1, $currentPage - 1)]) }}"
                    class="btn-secondary text-sm text-center {{ $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' }}"
                    style="padding:0.5rem 1rem;width:auto"
                >‹ Poprzednia</a>
                <span class="text-sm text-gray-400">{{ $currentPage }} / {{ $lastPage }}</span>
                <a
                    href="{{ route('users.index', $baseParams + ['page' => min($lastPage, $currentPage + 1)]) }}"
                    class="btn-secondary text-sm text-center {{ $currentPage >= $lastPage ? 'pointer-events-none opacity-40' : '' }}"
                    style="padding:0.5rem 1rem;width:auto"
                >Następna ›</a>
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
