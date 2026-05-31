@extends('layouts.app')
@section('title', 'Wiadomości')

@push('styles')
<style>
    .conv-item {
        background: #2a2a3e; border-radius: 1.25rem; padding: 1rem;
        display: flex; gap: 0.875rem; align-items: flex-start;
        border: 2px solid transparent; transition: border-color 0.15s;
        cursor: pointer;
    }
    .conv-item:active { border-color: #f59e0b; }
    .conv-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: #323248; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.1rem; color: #f59e0b;
        overflow: hidden;
    }
    .conv-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .conv-name { font-weight: 700; font-size: 0.9rem; color: #fff; }
    .conv-last { font-size: 0.78rem; color: #d1d5db; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px; }
    .conv-time { font-size: 0.65rem; color: #6b7280; flex-shrink: 0; margin-top: 2px; }
    .unread-badge {
        background: #f59e0b; color: #1a1a2e; border-radius: 999px;
        font-size: 0.65rem; font-weight: 700; padding: 1px 7px; flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="px-6 pt-6 pb-4 flex-shrink-0">
        <h1 class="text-xl font-bold text-white">Wiadomości</h1>
    </div>

    {{-- Lista konwersacji --}}
    <div class="flex-1 overflow-y-auto px-6 pb-4">
        @if(count($conversations) === 0)
            <div class="text-center py-16 text-gray-500">
                <div class="text-4xl mb-3">💬</div>
                <div class="font-semibold text-gray-400">Brak wiadomości</div>
                <div class="text-sm mt-1">Wiadomości pojawią się po wysłaniu zapytania<br>do znalazcy na mapie.</div>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach($conversations as $conv)
                @php
                    $other  = $conv['other_user'] ?? [];
                    $last   = $conv['last_message'] ?? null;
                    $unread = $conv['unread_count'] ?? 0;
                    $initials = strtoupper(substr($other['name'] ?? '?', 0, 1));
                @endphp
                <div class="conv-item" onclick="window.location='{{ route('messages.show', $conv['id']) }}'">
                    @if(!empty($other['id']))
                    <a href="{{ route('users.show', $other['id']) }}" onclick="event.stopPropagation()" class="conv-avatar">
                        @if(!empty($other['avatar_url']))
                            <img src="{{ $other['avatar_url'] }}" alt="">
                        @else
                            {{ $initials }}
                        @endif
                    </a>
                    @else
                    <div class="conv-avatar">
                        @if(!empty($other['avatar_url']))
                            <img src="{{ $other['avatar_url'] }}" alt="">
                        @else
                            {{ $initials }}
                        @endif
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="conv-name">{{ $other['name'] ?? 'Użytkownik' }}</div>
                        @if($last)
                        <div class="conv-last">{{ $last['body'] ?? '' }}</div>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        @if($last)
                        <div class="conv-time">
                            {{ \Carbon\Carbon::parse($last['sent_at'])->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, parts: 1) }}
                        </div>
                        @endif
                        @if($unread > 0)
                        <span class="unread-badge">{{ $unread }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Nawigacja --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('profile.show') }}" class="nav-item">
            <span class="nav-icon">👤</span><span>Profil</span>
        </a>
        <a href="{{ route('findings.map') }}" class="nav-item">
            <span class="nav-icon">🗺️</span><span>Mapa</span>
        </a>
        <a href="{{ route('findings.create') }}" class="nav-item">
            <span class="nav-icon">➕</span><span>Dodaj</span>
        </a>
        <span class="nav-item active">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </span>
    </div>

</div>
@endsection
