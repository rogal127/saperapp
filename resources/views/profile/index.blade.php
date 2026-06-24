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

    /* Akordeony znalezisk */
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

    .finding-card {
        display: flex; gap: 0.625rem; align-items: flex-start;
        padding: 0.625rem 1rem 0.625rem 0.75rem;
        border-bottom: 1px solid #252535;
        border-left: 3px solid transparent;
        cursor: pointer; transition: background 0.12s;
    }
    .finding-card:hover { background: #252538; }
    .finding-card:last-child { border-bottom: none; }
    .finding-card[data-type="archaeological_monument"] { border-left-color: #ef4444; }
    .finding-card[data-type="monument"] { border-left-color: #facc15; }
    .finding-card[data-type="non_monument"] { border-left-color: #22c55e; }
    .finding-thumb {
        width: 40px; height: 40px; border-radius: 0.5rem;
        object-fit: contain; flex-shrink: 0; background: #323248;
    }
    .finding-thumb-placeholder {
        width: 40px; height: 40px; border-radius: 0.5rem; flex-shrink: 0;
        background: #323248; display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .finding-card-name { font-size: 0.8rem; font-weight: 700; color: #fff; }
    .finding-card-meta { font-size: 0.68rem; color: #9ca3af; margin-top: 2px; }
    .finding-card-depth { font-size: 0.72rem; color: #f59e0b; font-weight: 600; margin-top: 2px; }

    /* Modal znaleziska */
    #finding-modal {
        display: none; position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.75); align-items: flex-end; justify-content: center;
    }
    #finding-modal.open { display: flex; }
    #finding-sheet {
        background: #1a1a2e; border-radius: 1.25rem 1.25rem 0 0;
        border: 1px solid #2a2a3e; width: 100%; max-width: 480px;
        max-height: 90vh; overflow-y: auto;
        animation: slideUp 0.25s ease;
    }
    @media (min-width: 768px) { #finding-sheet { max-width: 640px; } }
    @media (min-width: 1280px) { #finding-sheet { max-width: 820px; } }
    @keyframes slideUp {
        from { transform: translateY(40px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .fmodal-body { padding: 1rem; }
    .fmodal-name { font-weight: 700; font-size: 1rem; color: #fff; }
    .fmodal-meta { font-size: 0.75rem; color: #9ca3af; margin-top: 3px; }
    .fmodal-depth { font-size: 0.8rem; color: #f59e0b; font-weight: 600; margin-top: 4px; }
    .fmodal-desc { font-size: 0.8rem; color: #d1d5db; margin-top: 8px; }
    .fmodal-photo { width: 100%; max-height: 320px; object-fit: contain; border-radius: 0.75rem; margin-top: 0.75rem; background: #252538; }
    .fmodal-gallery { display: flex; gap: 6px; overflow-x: auto; scroll-snap-type: x mandatory; margin-top: 0.75rem; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .fmodal-gallery::-webkit-scrollbar { display: none; }
    .fmodal-gallery .fmodal-photo { flex: 0 0 90%; scroll-snap-align: start; margin-top: 0; }
    .fmodal-close { color: #9ca3af; font-size: 1.4rem; background: none; border: none; cursor: pointer; line-height: 1; }

    /* Export modal */
    #export-modal {
        display: none; position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.75); align-items: center; justify-content: center;
    }
    #export-modal.open { display: flex; }
    .export-sheet {
        background: #1a1a2e; border-radius: 1.25rem;
        border: 1px solid #2a2a3e; width: 90%; max-width: 380px;
        padding: 1.5rem; text-align: center;
        animation: slideUp 0.25s ease;
    }
    .export-title { font-weight: 700; font-size: 1rem; color: #fff; margin-bottom: 1rem; }
    .export-progress-wrap {
        background: #323248; border-radius: 999px; height: 12px;
        overflow: hidden; margin-bottom: 0.5rem;
    }
    .export-progress-bar {
        height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #f59e0b, #d97706);
        transition: width 0.3s ease;
        width: 0%;
    }
    .export-percent { font-size: 0.85rem; color: #f59e0b; font-weight: 700; margin-bottom: 0.25rem; }
    .export-message { font-size: 0.78rem; color: #9ca3af; margin-bottom: 1rem; min-height: 1.2em; }
    .export-done-btn {
        display: none; width: 100%; padding: 0.8rem;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #1a1a2e; font-weight: 700; border: none;
        border-radius: 0.75rem; cursor: pointer; font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    .export-close-btn {
        background: none; border: none; color: #6b7280;
        font-size: 0.8rem; cursor: pointer; padding: 0.3rem;
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

        {{-- Moje znaleziska --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <div class="section-title" style="margin-bottom:0">Moje znaleziska</div>
                @if(!empty($grouped))
                    <button onclick="startExport()" class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3)">
                        Eksportuj znaleziska
                    </button>
                @endif
            </div>
            @if(empty($grouped))
                <div class="text-center py-8 text-gray-500">
                    <div class="text-3xl mb-2">🔍</div>
                    <div class="text-sm">Brak znalezisk</div>
                </div>
            @else
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
                                            <div class="finding-card"
                                                data-type="{{ $finding['type'] ?? '' }}"
                                                data-id="{{ $finding['id'] ?? '' }}"
                                                data-name="{{ $finding['name'] ?? '' }}"
                                                data-depth="{{ $finding['depth_cm'] ?? 0 }}"
                                                data-date="{{ $finding['found_at'] ?? '' }}"
                                                data-desc="{{ $finding['description'] ?? '' }}"
                                                data-photo="{{ $finding['photo_url'] ?? '' }}"
                                                data-photos="{{ json_encode($finding['photos'] ?? []) }}"
                                                onclick="openFindingModal(this)">
                                                @php
                                                    $coverPhoto = $finding['photos'][0] ?? null;
                                                    $coverSrc = $coverPhoto
                                                        ? (! empty($coverPhoto['is_private'])
                                                            ? route('findings.photo', [$finding['id'], $coverPhoto['id']])
                                                            : ($coverPhoto['url'] ?? ''))
                                                        : ($finding['photo_url'] ?? '');
                                                @endphp
                                                @if($coverSrc)
                                                    <img src="{{ $coverSrc }}" alt="" class="finding-thumb">
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

    {{-- Modal znaleziska --}}
    <div id="finding-modal" onclick="handleFindingBackdrop(event)">
        <div id="finding-sheet">
            <div class="fmodal-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem">
                    <div>
                        <div class="fmodal-name" id="fmodal-name"></div>
                        <div class="fmodal-depth" id="fmodal-depth"></div>
                        <div class="fmodal-meta" id="fmodal-date"></div>
                    </div>
                    <button class="fmodal-close" onclick="closeFindingModal()">✕</button>
                </div>
                <div class="fmodal-desc" id="fmodal-desc"></div>
                <div id="fmodal-gallery" class="fmodal-gallery" style="display:none"></div>
                <div style="display:flex;gap:0.5rem;margin-top:0.875rem">
                    <a id="fmodal-edit-link" href="#"
                        style="flex:1;display:block;text-align:center;padding:0.8rem;background:transparent;border:1px solid #60a5fa;color:#60a5fa;border-radius:0.75rem;font-size:0.85rem;font-weight:700;text-decoration:none">
                        ✏️ Edytuj
                    </a>
                    <form id="fmodal-delete-form" method="POST" action="#" style="flex:1" onsubmit="return confirmDelete()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            style="width:100%;padding:0.8rem;background:transparent;border:1px solid #f87171;color:#f87171;border-radius:0.75rem;font-size:0.85rem;font-weight:700;cursor:pointer">
                            🗑️ Usuń
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Export modal --}}
    <div id="export-modal" onclick="if(event.target===this)closeExport()">
        <div class="export-sheet">
            <div class="export-title">Eksport znalezisk do PDF</div>
            <div class="export-percent" id="export-percent">0%</div>
            <div class="export-progress-wrap">
                <div class="export-progress-bar" id="export-bar"></div>
            </div>
            <div class="export-message" id="export-message">Inicjalizacja…</div>
            <a id="export-download-btn" class="export-done-btn" href="#" style="display:none;text-decoration:none;text-align:center">Pobierz PDF</a>
            <button class="export-close-btn" id="export-close-btn" onclick="closeExport()">Zamknij</button>
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
            <span class="nav-icon" style="font-weight:900;color:#f59e0b;">+</span><span>Dodaj</span>
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

let activeFindingId = null;

function openFindingModal(card) {
    activeFindingId = card.dataset.id || null;

    document.getElementById('fmodal-name').textContent  = '🪙 ' + (card.dataset.name || '');
    document.getElementById('fmodal-depth').textContent = '📏 ' + (card.dataset.depth || '0') + ' cm głębokości';
    document.getElementById('fmodal-date').textContent  = '📅 ' + (card.dataset.date || '');

    const desc = card.dataset.desc || '';
    const descEl = document.getElementById('fmodal-desc');
    descEl.textContent = desc;
    descEl.style.display = desc ? 'block' : 'none';

    let rawPhotos = [];
    try { rawPhotos = JSON.parse(card.dataset.photos || '[]'); } catch (e) { rawPhotos = []; }
    // Zdjęcia to obiekty {id, url, is_private}; prywatne ładujemy przez proxy (URL API wymaga Bearera).
    let photos = rawPhotos.map(p => {
        if (typeof p === 'string') { return p; }
        return p.is_private ? `/findings/${activeFindingId}/photos/${p.id}` : p.url;
    }).filter(Boolean);
    if (!photos.length && card.dataset.photo) { photos = [card.dataset.photo]; }

    const galleryEl = document.getElementById('fmodal-gallery');
    galleryEl.innerHTML = '';
    if (photos.length) {
        photos.forEach(url => {
            const img = document.createElement('img');
            img.className = 'fmodal-photo';
            img.src = url;
            img.alt = '';
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', () => openLightbox(url));
            galleryEl.appendChild(img);
        });
        galleryEl.style.display = photos.length > 1 ? 'flex' : 'block';
    } else {
        galleryEl.style.display = 'none';
    }

    if (activeFindingId) {
        const editLink = document.getElementById('fmodal-edit-link');
        const deleteForm = document.getElementById('fmodal-delete-form');
        if (editLink)   { editLink.href = `/findings/${activeFindingId}/edit`; }
        if (deleteForm) { deleteForm.action = `/findings/${activeFindingId}`; }
    }

    document.getElementById('finding-modal').classList.add('open');
}

function closeFindingModal() {
    document.getElementById('finding-modal').classList.remove('open');
    activeFindingId = null;
}

function handleFindingBackdrop(e) {
    if (e.target === document.getElementById('finding-modal')) { closeFindingModal(); }
}

function confirmDelete() {
    return confirm('Na pewno chcesz usunąć to znalezisko?');
}

// --- Export PDF ---
let exportPollTimer = null;

function startExport() {
    document.getElementById('export-modal').classList.add('open');
    document.getElementById('export-percent').textContent = '0%';
    document.getElementById('export-bar').style.width = '0%';
    document.getElementById('export-message').textContent = 'Rozpoczynanie eksportu…';
    document.getElementById('export-download-btn').style.display = 'none';

    fetch('{{ route("profile.findings-export.start") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.export_id) {
            pollExportProgress(data.export_id);
        } else {
            document.getElementById('export-message').textContent = 'Nie udało się rozpocząć eksportu.';
        }
    })
    .catch(() => {
        document.getElementById('export-message').textContent = 'Błąd połączenia.';
    });
}

function pollExportProgress(exportId) {
    clearInterval(exportPollTimer);
    exportPollTimer = setInterval(() => {
        fetch(`/profile/findings-export/${exportId}/progress`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('export-percent').textContent = data.percent + '%';
            document.getElementById('export-bar').style.width = data.percent + '%';
            document.getElementById('export-message').textContent = data.message;

            if (data.done) {
                clearInterval(exportPollTimer);
                const btn = document.getElementById('export-download-btn');
                btn.href = `/profile/findings-export/${exportId}/download`;
                btn.style.display = 'block';
                document.getElementById('export-message').textContent = 'Twój PDF jest gotowy!';
            } else if (data.failed) {
                clearInterval(exportPollTimer);
                document.getElementById('export-message').textContent = data.message || 'Eksport nie powiódł się.';
            }
        })
        .catch(() => {});
    }, 1000);
}

function closeExport() {
    clearInterval(exportPollTimer);
    document.getElementById('export-modal').classList.remove('open');
}
</script>
@endpush
