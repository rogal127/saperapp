@extends('layouts.app')
@section('title', ($profile['name'] ?? 'Profil') . ' – profil')

@push('styles')
<style>
    .profile-avatar {
        width: 80px; height: 80px; border-radius: 50%;
        border: 3px solid #f59e0b; object-fit: cover; flex-shrink: 0;
    }
    .profile-avatar-placeholder {
        width: 80px; height: 80px; border-radius: 50%;
        border: 3px solid #f59e0b; background: #323248;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: #f59e0b; font-weight: 700; flex-shrink: 0;
    }
    .stat-badge {
        background: #2a2a3e; border-radius: 0.875rem;
        padding: 0.5rem 1rem; text-align: center;
    }
    .stat-value { font-size: 1.3rem; font-weight: 800; color: #f59e0b; }
    .stat-label { font-size: 0.65rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.06em; }

    /* Akordeony */
    .acc-voi { background: #2a2a3e; border-radius: 1rem; overflow: hidden; margin-bottom: 0.5rem; }
    .acc-voi-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.875rem 1rem; cursor: pointer; user-select: none; gap: 0.5rem;
    }
    .acc-voi-header:active { background: #323250; }
    .acc-voi-name { font-weight: 700; font-size: 0.9rem; color: #fff; }
    .acc-voi-count { font-size: 0.72rem; color: #f59e0b; font-weight: 700; background: rgba(245,158,11,0.15); border-radius: 999px; padding: 2px 8px; flex-shrink: 0; }
    .acc-arrow { font-size: 0.85rem; color: #6b7280; transition: transform 0.2s; flex-shrink: 0; }
    .acc-arrow.open { transform: rotate(90deg); }

    .acc-voi-body { display: none; border-top: 1px solid #323248; }
    .acc-voi-body.open { display: block; }

    /* Poziom powiat */
    .acc-cou { border-bottom: 1px solid #323248; }
    .acc-cou:last-child { border-bottom: none; }
    .acc-cou-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.625rem 1rem 0.625rem 1.25rem; cursor: pointer; user-select: none; gap: 0.5rem;
    }
    .acc-cou-header:active { background: #2f2f48; }
    .acc-cou-name { font-size: 0.8rem; font-weight: 600; color: #e2e8f0; }
    .acc-cou-count { font-size: 0.65rem; color: #60a5fa; font-weight: 700; background: rgba(96,165,250,0.12); border-radius: 999px; padding: 2px 7px; flex-shrink: 0; }
    .acc-cou-arrow { font-size: 0.75rem; color: #6b7280; transition: transform 0.2s; flex-shrink: 0; }
    .acc-cou-arrow.open { transform: rotate(90deg); }

    .acc-cou-body { display: none; background: #1e1e32; }
    .acc-cou-body.open { display: block; }

    /* Poziom miejscowość */
    .acc-city { border-bottom: 1px solid #252535; }
    .acc-city:last-child { border-bottom: none; }
    .acc-city-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.5rem 1rem 0.5rem 1.5rem; cursor: pointer; user-select: none; gap: 0.5rem;
    }
    .acc-city-header:active { background: #232335; }
    .acc-city-name { font-size: 0.75rem; font-weight: 600; color: #34d399; }
    .acc-city-count { font-size: 0.6rem; color: #34d399; font-weight: 700; background: rgba(52,211,153,0.12); border-radius: 999px; padding: 1px 6px; flex-shrink: 0; }
    .acc-city-arrow { font-size: 0.7rem; color: #6b7280; transition: transform 0.2s; flex-shrink: 0; }
    .acc-city-arrow.open { transform: rotate(90deg); }

    .acc-city-body { display: none; }
    .acc-city-body.open { display: block; }

    /* Znalezisko */
    .finding-card {
        display: flex; gap: 0.625rem; align-items: flex-start;
        padding: 0.625rem 1rem 0.625rem 1.75rem;
        border-bottom: 1px solid #252535;
    }
    .finding-card:last-child { border-bottom: none; }
    .finding-thumb {
        width: 40px; height: 40px; border-radius: 0.5rem;
        object-fit: cover; flex-shrink: 0; background: #323248;
    }
    .finding-thumb-placeholder {
        width: 40px; height: 40px; border-radius: 0.5rem; flex-shrink: 0;
        background: #323248; display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .finding-card-name { font-size: 0.8rem; font-weight: 700; color: #fff; }
    .finding-card-meta { font-size: 0.68rem; color: #9ca3af; margin-top: 2px; }
    .finding-card-depth { font-size: 0.72rem; color: #f59e0b; font-weight: 600; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</button>
        <h1 class="text-lg font-bold text-white flex-1 truncate">Profil użytkownika</h1>
    </div>

    <div class="flex-1 overflow-y-auto px-4 pb-6">

        {{-- Karta profilu --}}
        <div class="flex items-center gap-4 py-5">
            @if(!empty($profile['avatar_url']))
                <img src="{{ $profile['avatar_url'] }}" alt="Avatar" class="profile-avatar">
            @else
                <div class="profile-avatar-placeholder">
                    {{ strtoupper(substr($profile['name'] ?? '?', 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="text-lg font-bold text-white truncate">{{ $profile['name'] ?? 'Użytkownik' }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Detektorysta</div>
            </div>
            <div class="stat-badge">
                <div class="stat-value">{{ $profile['findings_count'] ?? 0 }}</div>
                <div class="stat-label">znalezisk</div>
            </div>
        </div>

        {{-- Znaleziska --}}
        @if(empty($grouped))
            <div class="text-center py-12 text-gray-500">
                <div class="text-3xl mb-2">🔍</div>
                <div class="text-sm">Brak znalezisk</div>
            </div>
        @else
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Znaleziska według rejonu</div>
            <div id="accordion">
                @foreach($grouped as $voivodeship => $counties)
                @php
                    $voiCount = array_sum(array_map(fn($cities) => array_sum(array_map('count', $cities)), $counties));
                @endphp
                <div class="acc-voi">
                    <div class="acc-voi-header" onclick="toggleAcc(this)">
                        <span class="acc-voi-name">🗺️ {{ $voivodeship }}</span>
                        <div style="display:flex;align-items:center;gap:0.4rem;">
                            <span class="acc-voi-count">{{ $voiCount }}</span>
                            <span class="acc-arrow">›</span>
                        </div>
                    </div>
                    <div class="acc-voi-body">
                        @foreach($counties as $county => $cities)
                        @php
                            $couCount = array_sum(array_map('count', $cities));
                        @endphp
                        <div class="acc-cou">
                            <div class="acc-cou-header" onclick="toggleAcc(this)">
                                <span class="acc-cou-name">🏘️ {{ $county }}</span>
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    <span class="acc-cou-count">{{ $couCount }}</span>
                                    <span class="acc-cou-arrow">›</span>
                                </div>
                            </div>
                            <div class="acc-cou-body">
                                @foreach($cities as $city => $findings)
                                <div class="acc-city">
                                    <div class="acc-city-header" onclick="toggleAcc(this)">
                                        <span class="acc-city-name">📍 {{ $city }}</span>
                                        <div style="display:flex;align-items:center;gap:0.4rem;">
                                            <span class="acc-city-count">{{ count($findings) }}</span>
                                            <span class="acc-city-arrow">›</span>
                                        </div>
                                    </div>
                                    <div class="acc-city-body">
                                        @foreach($findings as $finding)
                                        <div class="finding-card">
                                            @if(!empty($finding['photo_url']))
                                                <img src="{{ $finding['photo_url'] }}" alt="" class="finding-thumb">
                                            @else
                                                <div class="finding-thumb-placeholder">🪙</div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <div class="finding-card-name">{{ $finding['name'] }}</div>
                                                <div class="finding-card-meta">📅 {{ $finding['found_at'] }}</div>
                                                <div class="finding-card-depth">📏 {{ $finding['depth_cm'] }} cm</div>
                                                @if(!empty($finding['description']))
                                                    <div class="finding-card-meta" style="margin-top:3px">{{ Str::limit($finding['description'], 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
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
        <a href="{{ route('messages.index') }}" class="nav-item" id="nav-messages">
            <span class="nav-icon">💬</span><span>Wiadomości</span>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
function toggleAcc(header) {
    const body = header.nextElementSibling;
    const arrow = header.querySelector('.acc-arrow, .acc-cou-arrow, .acc-city-arrow');
    const isOpen = body.classList.toggle('open');
    if (arrow) arrow.classList.toggle('open', isOpen);
}

</script>
@endpush
