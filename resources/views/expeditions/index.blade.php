@extends('layouts.app')
@section('title', 'Poszukiwania – Historius')

@push('styles')
<style>
    .tab-btn { flex: 1; padding: 0.65rem 0; font-size: 0.85rem; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; }
    .tab-btn.active { color: #f59e0b; border-bottom-color: #f59e0b; }
    .tab-panel { display: none; }
    .tab-panel.active { display: flex; }
    .phase-badge { display:inline-flex; align-items:center; gap:4px; border-radius:0.5rem; padding:0.15rem 0.5rem; font-size:0.65rem; font-weight:600; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 class="text-lg font-bold text-white flex-1 truncate">Poszukiwania</h1>
        <button id="joinCodeBtn" class="text-amber-400 text-sm font-semibold whitespace-nowrap">🔑 Kod</button>
    </div>

    @if(session('success'))
    <div class="mx-4 mt-3 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex px-4 border-b border-surface-card flex-shrink-0">
        <button class="tab-btn active" data-tab="mine">Moje</button>
        <button class="tab-btn" data-tab="participating">Uczestniczę</button>
        <button class="tab-btn" data-tab="discover">Odkrywaj</button>
    </div>

    {{-- Panels --}}
    <div id="paneMine" class="tab-panel active flex-1 overflow-y-auto px-4 py-3 flex-col gap-3">
        <div class="text-gray-400 text-sm text-center py-8" data-loading>Ładowanie...</div>
    </div>
    <div id="paneParticipating" class="tab-panel flex-1 overflow-y-auto px-4 py-3 flex-col gap-3">
        <div class="text-gray-400 text-sm text-center py-8" data-loading>Ładowanie...</div>
    </div>
    <div id="paneDiscover" class="tab-panel flex-1 overflow-y-auto px-4 py-3 flex-col gap-3">
        <div class="text-gray-400 text-sm text-center py-8" data-loading>Ładowanie...</div>
    </div>

    {{-- Create button --}}
    <div class="px-4 py-3 border-t border-surface-card flex-shrink-0">
        <a href="{{ route('expeditions.create') }}" class="btn-primary block text-center">➕ Dodaj poszukiwanie</a>
    </div>

</div>

{{-- Join by code modal --}}
<div id="joinCodeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-6" style="background:rgba(0,0,0,0.6)">
    <div class="card w-full max-w-sm">
        <h3 class="text-base font-bold text-white mb-1">Dołącz kodem</h3>
        <p class="text-xs text-gray-400 mb-3">Wpisz kod otrzymany od kierownika poszukiwania.</p>
        <input type="text" id="joinCodeInput" class="input-field mb-3 text-center tracking-widest uppercase" placeholder="np. AB12CD" maxlength="6">
        <p id="joinCodeError" class="text-red-400 text-xs mb-2 hidden"></p>
        <div class="flex gap-2">
            <button id="joinCodeCancel" class="btn-secondary">Anuluj</button>
            <button id="joinCodeSubmit" class="btn-primary">Dołącz</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const API = "{{ route('expeditions.api') }}";
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const phaseMap = {
        upcoming: { label: 'Nadchodzące', bg: 'rgba(96,165,250,0.15)', color: '#60a5fa' },
        active:   { label: 'W trakcie',   bg: 'rgba(52,211,153,0.15)', color: '#34d399' },
        finished: { label: 'Zakończone',  bg: 'rgba(148,163,184,0.15)', color: '#94a3b8' },
    };

    function phaseBadge(phase) {
        const p = phaseMap[phase];
        if (!p) { return ''; }
        return '<span class="phase-badge" style="background:' + p.bg + ';color:' + p.color + '">● ' + p.label + '</span>';
    }

    function statusHint(e) {
        if (e.is_leader) { return '<span class="text-xs text-amber-400">Jesteś kierownikiem</span>'; }
        if (e.my_status === 'accepted') { return '<span class="text-xs text-green-400">Uczestniczysz</span>'; }
        if (e.my_status === 'invited') { return '<span class="text-xs text-blue-400">Zaproszenie</span>'; }
        if (e.my_status === 'requested') { return '<span class="text-xs text-gray-400">Prośba wysłana</span>'; }
        return '';
    }

    function renderCard(e) {
        return '<a href="/expeditions/' + e.id + '" class="card block active:scale-95 transition-transform">' +
            '<div class="flex items-start justify-between gap-2">' +
                '<h3 class="font-bold text-white text-base flex-1 min-w-0 truncate">' + (e.name || '') + '</h3>' +
                phaseBadge(e.phase) +
            '</div>' +
            '<p class="text-xs text-gray-400 mt-1">📅 ' + (e.starts_at || '') + ' — ' + (e.ends_at || '') + '</p>' +
            '<div class="flex items-center gap-3 mt-2 text-xs text-gray-500">' +
                '<span>👥 ' + (e.participants_count ?? 0) + '</span>' +
                '<span>⚒️ ' + (e.findings_count ?? 0) + '</span>' +
                (e.leader ? '<span class="truncate">🧭 ' + e.leader.name + '</span>' : '') +
            '</div>' +
            '<div class="mt-1.5">' + statusHint(e) + '</div>' +
        '</a>';
    }

    const panes = {
        mine:          { el: document.getElementById('paneMine'),          loaded: false, empty: 'Nie prowadzisz jeszcze żadnego poszukiwania.' },
        participating: { el: document.getElementById('paneParticipating'), loaded: false, empty: 'Nie uczestniczysz w żadnym poszukiwaniu.' },
        discover:      { el: document.getElementById('paneDiscover'),      loaded: false, empty: 'Brak publicznych poszukiwań.' },
    };

    function loadPane(scope, page) {
        page = page || 1;
        const pane = panes[scope].el;
        if (page === 1) {
            pane.innerHTML = '<div class="text-gray-400 text-sm text-center py-8">Ładowanie...</div>';
        }

        const url = new URL(API, window.location.origin);
        url.searchParams.set('scope', scope);
        url.searchParams.set('page', page);

        fetch(url)
            .then(r => r.json())
            .then(res => {
                const items = res.data || [];
                const moreBtn = pane.querySelector('[data-more]');
                if (moreBtn) { moreBtn.remove(); }

                if (page === 1) {
                    if (!items.length) {
                        pane.innerHTML = '<div class="flex flex-col items-center justify-center py-12 text-gray-500">' +
                            '<div class="text-4xl mb-3">🧭</div><p class="text-sm text-center">' + panes[scope].empty + '</p></div>';
                        return;
                    }
                    pane.innerHTML = '';
                }
                pane.insertAdjacentHTML('beforeend', items.map(renderCard).join(''));

                const meta = res.meta || {};
                if (meta.current_page && meta.last_page && meta.current_page < meta.last_page) {
                    pane.insertAdjacentHTML('beforeend',
                        '<button data-more class="btn-secondary flex-shrink-0">Pokaż więcej</button>');
                    pane.querySelector('[data-more]').addEventListener('click', function () {
                        this.disabled = true;
                        this.textContent = 'Ładowanie...';
                        loadPane(scope, meta.current_page + 1);
                    });
                }
            })
            .catch(() => {
                if (page === 1) {
                    pane.innerHTML = '<div class="text-red-400 text-sm text-center py-8">Błąd ładowania.</div>';
                    return;
                }
                const moreBtn = pane.querySelector('[data-more]');
                if (moreBtn) {
                    moreBtn.disabled = false;
                    moreBtn.textContent = 'Błąd — spróbuj ponownie';
                }
            });
    }

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            Object.entries(panes).forEach(([scope, p]) => p.el.classList.toggle('active', scope === tab));
            if (!panes[tab].loaded) {
                panes[tab].loaded = true;
                loadPane(tab);
            }
        });
    });

    panes.mine.loaded = true;
    loadPane('mine');

    // Join by code modal
    const modal = document.getElementById('joinCodeModal');
    const input = document.getElementById('joinCodeInput');
    const error = document.getElementById('joinCodeError');

    document.getElementById('joinCodeBtn').addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        input.focus();
    });
    document.getElementById('joinCodeCancel').addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        error.classList.add('hidden');
    });
    document.getElementById('joinCodeSubmit').addEventListener('click', () => {
        const code = input.value.trim().toUpperCase();
        error.classList.add('hidden');
        if (!code) { return; }
        fetch("{{ route('expeditions.join-code') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ code }),
        })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(({ ok, data }) => {
            if (ok && data.redirect) { window.location.href = data.redirect; return; }
            error.textContent = data.message || 'Nie udało się dołączyć.';
            error.classList.remove('hidden');
        })
        .catch(() => {
            error.textContent = 'Błąd połączenia.';
            error.classList.remove('hidden');
        });
    });
})();
</script>
@endpush
