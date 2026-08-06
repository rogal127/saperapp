@extends('layouts.app')
@section('title', 'Imprezy – Historius')

@push('styles')
<style>
    .tab-btn { flex: 1; padding: 0.65rem 0; font-size: 0.85rem; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; position: relative; }
    .tab-btn.active { color: #f59e0b; border-bottom-color: #f59e0b; }
    .tab-panel { display: none; }
    .tab-panel.active { display: flex; }
    .month-header { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #9ca3af; margin: 0.5rem 0 0.1rem 0.25rem; }
    .status-pill { display:inline-flex; align-items:center; gap:4px; border-radius:0.5rem; padding:0.15rem 0.5rem; font-size:0.65rem; font-weight:600; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 class="text-lg font-bold text-white flex-1 truncate">Imprezy</h1>
    </div>

    @if(session('success'))
    <div class="mx-4 mt-3 px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex px-4 border-b border-surface-card flex-shrink-0">
        <button class="tab-btn active" data-tab="upcoming">Nadchodzące</button>
        <button class="tab-btn" data-tab="mine">Moje</button>
        @if(session('api_user.is_admin'))
        <button class="tab-btn" data-tab="pending">
            Moderacja
            <span id="pendingTabBadge" class="hidden absolute top-1 right-2 bg-amber-500 text-black text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 inline-flex items-center justify-center"></span>
        </button>
        @endif
    </div>

    {{-- Voivodeship filter (upcoming only) --}}
    <div id="filterBar" class="px-4 pt-3 flex-shrink-0">
        <select id="voivodeshipFilter" class="input-field text-sm" style="padding:0.5rem 0.75rem">
            <option value="">🌍 Wszystkie województwa</option>
            @foreach($voivodeships as $voivodeship)
            <option value="{{ $voivodeship }}">{{ $voivodeship }}</option>
            @endforeach
        </select>
    </div>

    {{-- Panels --}}
    <div id="paneUpcoming" class="tab-panel active flex-1 overflow-y-auto px-4 py-3 flex-col gap-2">
        <div class="text-gray-400 text-sm text-center py-8">Ładowanie...</div>
    </div>
    <div id="paneMine" class="tab-panel flex-1 overflow-y-auto px-4 py-3 flex-col gap-2">
        <div class="text-gray-400 text-sm text-center py-8">Ładowanie...</div>
    </div>
    @if(session('api_user.is_admin'))
    <div id="panePending" class="tab-panel flex-1 overflow-y-auto px-4 py-3 flex-col gap-2">
        <div class="text-gray-400 text-sm text-center py-8">Ładowanie...</div>
    </div>
    @endif

    {{-- Create button --}}
    <div class="px-4 py-3 border-t border-surface-card flex-shrink-0">
        <a href="{{ route('events.create') }}" class="btn-primary block text-center">➕ Dodaj imprezę</a>
    </div>

</div>

{{-- Reject reason modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center p-6" style="background:rgba(0,0,0,0.6)">
    <div class="card w-full max-w-sm">
        <h3 class="text-base font-bold text-white mb-1">Odrzuć imprezę</h3>
        <p class="text-xs text-gray-400 mb-3">Podaj powód — zobaczy go osoba zgłaszająca.</p>
        <textarea id="rejectReason" rows="3" class="input-field mb-3 resize-none" placeholder="np. Brak dokładnej lokalizacji"></textarea>
        <p id="rejectError" class="text-red-400 text-xs mb-2 hidden"></p>
        <div class="flex gap-2">
            <button id="rejectCancel" class="btn-secondary">Anuluj</button>
            <button id="rejectSubmit" class="btn-primary">Odrzuć</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const API = "{{ route('events.api') }}";
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const IS_ADMIN = @json((bool) session('api_user.is_admin'));

    const MONTHS = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
    const MONTHS_GEN = ['stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];

    function parseDate(s) {
        const [y, m, d] = (s || '').split('-').map(Number);
        return { y, m, d };
    }

    function formatRange(startsAt, endsAt) {
        const a = parseDate(startsAt);
        const b = parseDate(endsAt);
        if (!a.y || !b.y) { return startsAt || ''; }
        if (a.y === b.y && a.m === b.m && a.d === b.d) {
            return `${a.d} ${MONTHS_GEN[a.m - 1]} ${a.y}`;
        }
        if (a.y === b.y && a.m === b.m) {
            return `${a.d}–${b.d} ${MONTHS_GEN[a.m - 1]} ${a.y}`;
        }
        if (a.y === b.y) {
            return `${a.d} ${MONTHS_GEN[a.m - 1]} – ${b.d} ${MONTHS_GEN[b.m - 1]} ${a.y}`;
        }
        return `${a.d} ${MONTHS_GEN[a.m - 1]} ${a.y} – ${b.d} ${MONTHS_GEN[b.m - 1]} ${b.y}`;
    }

    const statusMap = {
        pending:  { label: '⏳ Czeka na akceptację', bg: 'rgba(245,158,11,0.15)', color: '#fbbf24' },
        approved: { label: '✅ Zaakceptowana',       bg: 'rgba(52,211,153,0.15)', color: '#34d399' },
        rejected: { label: '⛔ Odrzucona',           bg: 'rgba(248,113,113,0.15)', color: '#f87171' },
    };

    function statusPill(status) {
        const s = statusMap[status];
        if (!s) { return ''; }
        return '<span class="status-pill" style="background:' + s.bg + ';color:' + s.color + '">' + s.label + '</span>';
    }

    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function renderCard(e, extraHtml) {
        const thumb = e.photo_thumb_url
            ? '<img src="' + esc(e.photo_thumb_url) + '" alt="" class="w-16 h-16 rounded-xl object-cover flex-shrink-0" loading="lazy">'
            : '<div class="w-16 h-16 rounded-xl bg-rose-500/20 flex items-center justify-center text-2xl flex-shrink-0">🎉</div>';
        const active = e.phase === 'active'
            ? '<span class="status-pill" style="background:rgba(52,211,153,0.15);color:#34d399">● Trwa</span>'
            : '';

        return '<a href="/events/' + e.id + '" class="card block active:scale-95 transition-transform" style="padding:0.75rem">' +
            '<div class="flex items-center gap-3">' +
                thumb +
                '<div class="flex-1 min-w-0">' +
                    '<div class="flex items-center gap-2">' +
                        '<h3 class="font-bold text-white text-sm flex-1 min-w-0 truncate">' + esc(e.name) + '</h3>' + active +
                    '</div>' +
                    '<p class="text-xs text-amber-400 font-semibold mt-0.5">📅 ' + formatRange(e.starts_at, e.ends_at) + '</p>' +
                    '<p class="text-xs text-gray-500 mt-0.5 truncate">📍 woj. ' + esc(e.voivodeship) +
                        (e.organizer ? ' · ' + esc(e.organizer.name) : '') + '</p>' +
                '</div>' +
                '<span class="text-gray-500 text-lg">›</span>' +
            '</div>' +
            (extraHtml || '') +
        '</a>';
    }

    // Nadchodzące: lista pogrupowana po miesiącach (kalendarz-agenda).
    function renderUpcoming(pane, items) {
        let html = '';
        let lastKey = '';
        items.forEach(e => {
            const d = parseDate(e.starts_at);
            const key = d.y + '-' + d.m;
            if (key !== lastKey) {
                lastKey = key;
                html += '<div class="month-header">' + MONTHS[d.m - 1] + ' ' + d.y + '</div>';
            }
            html += renderCard(e);
        });
        pane.insertAdjacentHTML('beforeend', html);
    }

    function renderMine(pane, items) {
        pane.insertAdjacentHTML('beforeend', items.map(e => {
            let extra = '<div class="mt-2">' + statusPill(e.status) + '</div>';
            if (e.status === 'rejected' && e.rejection_reason) {
                extra += '<p class="text-xs text-red-300/80 mt-1.5">Powód: ' + esc(e.rejection_reason) + '</p>';
            }
            return renderCard(e, extra);
        }).join(''));
    }

    function renderPending(pane, items) {
        pane.insertAdjacentHTML('beforeend', items.map(e => {
            const extra =
                '<div class="flex gap-2 mt-3" data-moderation>' +
                    '<button type="button" class="btn-secondary text-sm" style="padding:0.5rem" data-reject="' + e.id + '">⛔ Odrzuć</button>' +
                    '<button type="button" class="btn-primary text-sm" style="padding:0.5rem" data-approve="' + e.id + '">✅ Zatwierdź</button>' +
                '</div>';
            return renderCard(e, extra);
        }).join(''));
    }

    const panes = {
        upcoming: { el: document.getElementById('paneUpcoming'), render: renderUpcoming, empty: 'Brak nadchodzących imprez.' },
        mine:     { el: document.getElementById('paneMine'), render: renderMine, empty: 'Nie zgłosiłeś jeszcze żadnej imprezy.' },
        pending:  { el: document.getElementById('panePending'), render: renderPending, empty: 'Brak imprez do akceptacji. 🎉' },
    };

    function loadPane(name, page) {
        const pane = panes[name];
        if (!pane.el) { return; }
        if (!page || page === 1) {
            pane.el.innerHTML = '<div class="text-gray-400 text-sm text-center py-8">Ładowanie...</div>';
        }

        const url = new URL(API, window.location.origin);
        if (name !== 'upcoming') { url.searchParams.set('scope', name); }
        if (name === 'upcoming') {
            const voivodeship = document.getElementById('voivodeshipFilter').value;
            if (voivodeship) { url.searchParams.set('voivodeship', voivodeship); }
        }
        if (page) { url.searchParams.set('page', page); }

        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (!page || page === 1) { pane.el.innerHTML = ''; }
                pane.el.querySelector('[data-more]')?.remove();
                const items = res.data || [];
                if (!items.length && (!page || page === 1)) {
                    pane.el.innerHTML = '<div class="flex flex-col items-center justify-center py-12 text-gray-500">' +
                        '<div class="text-4xl mb-3">🎪</div><p class="text-sm text-center">' + pane.empty + '</p></div>';
                    if (name === 'pending') { updatePendingBadge(0); }
                    return;
                }
                pane.render(pane.el, items);
                if (name === 'pending') { updatePendingBadge(res.meta?.total ?? items.length); }
                const meta = res.meta || {};
                if (meta.current_page && meta.last_page && meta.current_page < meta.last_page) {
                    pane.el.insertAdjacentHTML('beforeend',
                        '<button type="button" data-more class="btn-secondary text-sm mt-1">Pokaż więcej</button>');
                    pane.el.querySelector('[data-more]').addEventListener('click', () => loadPane(name, meta.current_page + 1));
                }
            })
            .catch(() => {
                pane.el.innerHTML = '<div class="text-red-400 text-sm text-center py-8">Błąd ładowania.</div>';
            });
    }

    function updatePendingBadge(count) {
        const badge = document.getElementById('pendingTabBadge');
        if (!badge) { return; }
        badge.textContent = count > 9 ? '9+' : count;
        badge.classList.toggle('hidden', count <= 0);
    }

    const loaded = { upcoming: false, mine: false, pending: false };

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            Object.entries(panes).forEach(([name, pane]) => pane.el?.classList.toggle('active', name === tab));
            document.getElementById('filterBar').style.display = tab === 'upcoming' ? '' : 'none';
            if (!loaded[tab]) {
                loaded[tab] = true;
                loadPane(tab);
            }
        });
    });

    document.getElementById('voivodeshipFilter').addEventListener('change', () => loadPane('upcoming'));

    loaded.upcoming = true;
    loadPane('upcoming');

    if (IS_ADMIN) {
        fetch("{{ route('events.pending-count') }}")
            .then(r => r.json())
            .then(data => updatePendingBadge(data.count || 0))
            .catch(() => {});
    }

    // ----- Moderation actions -----
    const modal = document.getElementById('rejectModal');
    const reasonInput = document.getElementById('rejectReason');
    const rejectError = document.getElementById('rejectError');
    let rejectId = null;

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        rejectError.classList.add('hidden');
        reasonInput.value = '';
        rejectId = null;
    }

    document.addEventListener('click', (event) => {
        const approveBtn = event.target.closest('[data-approve]');
        const rejectBtn = event.target.closest('[data-reject]');
        if (!approveBtn && !rejectBtn) { return; }
        event.preventDefault();

        if (approveBtn) {
            approveBtn.disabled = true;
            fetch('/api/events/' + approveBtn.dataset.approve + '/approve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => { if (!r.ok) { throw new Error(); } })
            .then(() => { loadPane('pending'); loaded.upcoming = false; })
            .catch(() => { approveBtn.disabled = false; alert('Nie udało się zatwierdzić imprezy.'); });
            return;
        }

        rejectId = rejectBtn.dataset.reject;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        reasonInput.focus();
    });

    document.getElementById('rejectCancel').addEventListener('click', closeModal);
    document.getElementById('rejectSubmit').addEventListener('click', () => {
        const reason = reasonInput.value.trim();
        if (!reason) {
            rejectError.textContent = 'Podaj powód odrzucenia.';
            rejectError.classList.remove('hidden');
            return;
        }
        fetch('/api/events/' + rejectId + '/reject', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason }),
        })
        .then(r => { if (!r.ok) { throw new Error(); } })
        .then(() => { closeModal(); loadPane('pending'); })
        .catch(() => {
            rejectError.textContent = 'Nie udało się odrzucić imprezy.';
            rejectError.classList.remove('hidden');
        });
    });
})();
</script>
@endpush
