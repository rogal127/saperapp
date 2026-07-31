@extends('layouts.app')
@section('title', ($expedition['name'] ?? 'Poszukiwanie') . ' – Historius')

@php
    $isLeader = $expedition['is_leader'] ?? false;
    $myStatus = $expedition['my_status'] ?? null;
@endphp

@push('styles')
<style>
    #map-view { height: 220px; border-radius: 1rem; overflow: hidden; }
    .tab-btn { flex: 1; padding: 0.65rem 0; font-size: 0.85rem; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; }
    .tab-btn.active { color: #f59e0b; border-bottom-color: #f59e0b; }
    .tab-panel { display: none; }
    .tab-panel.active { display: flex; flex-direction: column; }
    .autocomplete-list { position:absolute; top:100%; left:0; right:0; z-index:50; background:#323248; border:1px solid #404060; border-top:none; border-radius:0 0 0.875rem 0.875rem; max-height:200px; overflow-y:auto; }
    .autocomplete-item { padding:0.5rem 0.75rem; font-size:0.85rem; color:#e2e8f0; cursor:pointer; }
    .autocomplete-item:active { background:#404060; }

    /* Export modal */
    @keyframes slideUp { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
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
    .export-trigger-btn {
        width: 100%; padding: 0.7rem;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #1a1a2e; font-weight: 700; border: none;
        border-radius: 0.75rem; cursor: pointer; font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('expeditions.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 class="text-lg font-bold text-white flex-1 truncate">{{ $expedition['name'] ?? 'Poszukiwanie' }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="flex px-4 border-b border-surface-card flex-shrink-0">
        <button class="tab-btn active" data-tab="info">Info</button>
        @if($isLeader || $myStatus === 'accepted')
        <button class="tab-btn" data-tab="participants">Uczestnicy</button>
        @endif
        @if($isLeader)
        <button class="tab-btn" data-tab="findings">Znaleziska</button>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-4">

        {{-- === INFO === --}}
        <div id="paneInfo" class="tab-panel active flex-col gap-4">
            <div id="map-view"></div>

            <div class="card flex flex-col gap-2">
                <div class="flex items-center gap-3 text-sm text-gray-300">
                    <span>📅</span><span>{{ $expedition['starts_at'] ?? '' }} — {{ $expedition['ends_at'] ?? '' }}</span>
                </div>
                @if(!empty($expedition['leader']))
                <div class="flex items-center gap-3 text-sm text-gray-300">
                    <span>🧭</span><a href="{{ route('users.show', $expedition['leader']['id']) }}" class="text-amber-400">{{ $expedition['leader']['name'] }}</a>
                </div>
                @endif
                <div class="flex items-center gap-4 text-sm text-gray-400">
                    <span>👥 {{ $expedition['participants_count'] ?? 0 }} uczestników</span>
                    <span>⚒️ {{ $expedition['findings_count'] ?? 0 }} znalezisk</span>
                </div>
            </div>

            @if(!empty($expedition['description']))
            <div class="card">
                <p class="text-sm text-gray-300 whitespace-pre-line">{{ $expedition['description'] }}</p>
            </div>
            @endif

            {{-- Leader: join code --}}
            @if($isLeader && !empty($expedition['join_code']))
            <div class="card">
                <p class="text-xs text-gray-500 mb-1">Kod dołączenia (udostępnij uczestnikom)</p>
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold tracking-widest text-amber-400">{{ $expedition['join_code'] }}</span>
                    <button id="copyCodeBtn" class="text-xs text-amber-400 font-semibold" data-code="{{ $expedition['join_code'] }}">Kopiuj</button>
                </div>
            </div>
            @endif

            {{-- Non-leader actions --}}
            @unless($isLeader)
                @if($myStatus === 'accepted')
                <div class="card text-center text-sm text-green-400">✓ Uczestniczysz w tym poszukiwaniu</div>
                <button id="leaveExpeditionBtn" class="btn-secondary text-red-400">Wypisz się z poszukiwania</button>
                @elseif($myStatus === 'invited')
                <div class="card flex flex-col gap-2">
                    <p class="text-sm text-blue-300 text-center">Zostałeś zaproszony do tego poszukiwania.</p>
                    <div class="flex gap-2">
                        <button class="btn-secondary" data-my-action="decline">Odrzuć</button>
                        <button class="btn-primary" data-my-action="accept">Akceptuj</button>
                    </div>
                </div>
                @elseif($myStatus === 'requested')
                <div class="card text-center text-sm text-gray-400">Prośba o dołączenie wysłana — oczekuje na akceptację kierownika.</div>
                @else
                <button id="requestJoinBtn" class="btn-primary">Poproś o dołączenie</button>
                @endif
            @endunless

            @if($isLeader)
            <button id="deleteExpeditionBtn" class="btn-secondary text-red-400">🗑️ Usuń poszukiwanie</button>
            @endif
        </div>

        {{-- === PARTICIPANTS === --}}
        @if($isLeader || $myStatus === 'accepted')
        <div id="paneParticipants" class="tab-panel flex-col gap-3">
            @if($isLeader)
            <div class="relative">
                <input type="text" id="inviteInput" class="input-field text-sm" placeholder="🔍 Zaproś użytkownika po nazwie...">
                <div id="inviteAutocomplete" class="autocomplete-list" style="display:none"></div>
            </div>
            @endif
            <div id="participantsList" class="flex flex-col gap-2">
                <p class="text-gray-400 text-sm text-center py-4">Ładowanie...</p>
            </div>
        </div>
        @endif

        {{-- === FINDINGS (leader) === --}}
        @if($isLeader)
        <div id="paneFindings" class="tab-panel flex-col gap-3">
            <p class="text-xs text-gray-500">Wszystkie znaleziska przypięte do poszukiwania przez uczestników — także prywatne.</p>
            <button id="exportFindingsBtn" class="export-trigger-btn" onclick="startExpeditionExport()">📄 Eksportuj znaleziska do PDF</button>
            <div id="findingsList" class="flex flex-col gap-3">
                <p class="text-gray-400 text-sm text-center py-4">Ładowanie...</p>
            </div>
        </div>
        @endif

    </div>

    {{-- Export progress modal --}}
    @if($isLeader)
    <div id="export-modal" onclick="if(event.target===this)closeExpeditionExport()">
        <div class="export-sheet">
            <div class="export-title">Eksport znalezisk do PDF</div>
            <div class="export-percent" id="export-percent">0%</div>
            <div class="export-progress-wrap">
                <div class="export-progress-bar" id="export-bar"></div>
            </div>
            <div class="export-message" id="export-message">Inicjalizacja…</div>
            <div id="export-hint" style="font-size:0.7rem;color:#6b7280;margin-bottom:0.75rem">Możesz zamknąć to okno i wrócić później — eksport będzie kontynuowany w tle.</div>
            <a id="export-download-btn" class="export-done-btn" href="#" style="display:none;text-decoration:none;text-align:center">Pobierz PDF</a>
            <button class="export-close-btn" onclick="closeExpeditionExport()">Zamknij</button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const EXP = @json($expedition);
    const EXP_ID = EXP.id;
    const IS_LEADER = @json($isLeader);
    const CURRENT_USER_ID = @json(session('api_user.id'));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    // --- Map with polygon ---
    const map = L.map('map-view', { zoomControl: false, attributionControl: false, dragging: true, tap: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    try {
        const layer = L.geoJSON(EXP.area, {
            style: { color: '#f59e0b', weight: 2, fillColor: '#f59e0b', fillOpacity: 0.2 },
        }).addTo(map);
        const bounds = layer.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        } else {
            map.setView([52.0, 19.0], 6);
        }
    } catch (e) { map.setView([52.0, 19.0], 6); }

    // --- Tabs ---
    const panes = { info: document.getElementById('paneInfo'), participants: document.getElementById('paneParticipants'), findings: document.getElementById('paneFindings') };
    let participantsLoaded = false, findingsLoaded = false;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            Object.entries(panes).forEach(([k, el]) => { if (el) { el.classList.toggle('active', k === btn.dataset.tab); } });
            if (btn.dataset.tab === 'participants' && !participantsLoaded) { participantsLoaded = true; renderParticipants(); }
            if (btn.dataset.tab === 'findings' && !findingsLoaded) { findingsLoaded = true; loadFindings(); }
            if (btn.dataset.tab === 'info') { setTimeout(() => map.invalidateSize(), 50); }
        });
    });
    setTimeout(() => map.invalidateSize(), 100);

    // --- Copy code ---
    const copyBtn = document.getElementById('copyCodeBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            navigator.clipboard?.writeText(copyBtn.dataset.code);
            copyBtn.textContent = 'Skopiowano!';
            setTimeout(() => copyBtn.textContent = 'Kopiuj', 1500);
        });
    }

    // --- Non-leader participation actions ---
    const requestJoinBtn = document.getElementById('requestJoinBtn');
    if (requestJoinBtn) {
        requestJoinBtn.addEventListener('click', () => {
            fetch("/api/expeditions/" + EXP_ID + "/join", { method: 'POST', headers: jsonHeaders })
                .then(r => r.json()).then(() => location.reload()).catch(() => alert('Błąd.'));
        });
    }
    document.querySelectorAll('[data-my-action]').forEach(btn => {
        btn.addEventListener('click', () => {
            // Find my own participation row (matched by the logged-in user's id).
            const mine = (EXP.participants || []).find(p => p.user && p.user.id === CURRENT_USER_ID && p.status === 'invited');
            if (!mine) { location.reload(); return; }
            const action = btn.dataset.myAction; // accept | decline
            fetch("/api/expeditions/" + EXP_ID + "/participants/" + mine.id + "/" + action, { method: 'POST', headers: jsonHeaders })
                .then(r => r.json()).then(() => location.reload()).catch(() => alert('Błąd.'));
        });
    });

    // --- Leave expedition (self) ---
    const leaveBtn = document.getElementById('leaveExpeditionBtn');
    if (leaveBtn) {
        leaveBtn.addEventListener('click', () => {
            if (!confirm('Na pewno chcesz wypisać się z tego poszukiwania?')) { return; }
            const mine = (EXP.participants || []).find(p => p.user && p.user.id === CURRENT_USER_ID && p.status === 'accepted');
            if (!mine) { location.reload(); return; }
            leaveBtn.disabled = true;
            fetch("/api/expeditions/" + EXP_ID + "/participants/" + mine.id, { method: 'DELETE', headers: jsonHeaders })
                .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
                .then(({ ok, data }) => {
                    if (!ok) { alert(data.message || 'Błąd.'); leaveBtn.disabled = false; return; }
                    window.location.href = "{{ route('expeditions.index') }}";
                })
                .catch(() => { alert('Błąd.'); leaveBtn.disabled = false; });
        });
    }

    // --- Delete expedition ---
    const delBtn = document.getElementById('deleteExpeditionBtn');
    if (delBtn) {
        delBtn.addEventListener('click', () => {
            if (!confirm('Na pewno usunąć to poszukiwanie? Znaleziska uczestników zostaną odpięte.')) { return; }
            fetch("/api/expeditions/" + EXP_ID, { method: 'DELETE', headers: jsonHeaders })
                .then(async r => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => { if (ok && data.redirect) { window.location.href = data.redirect; } else { alert(data.message || 'Błąd.'); } })
                .catch(() => alert('Błąd.'));
        });
    }

    // --- Participants ---
    const statusLabels = { accepted: ['Uczestnik', 'text-green-400'], invited: ['Zaproszony', 'text-blue-400'], requested: ['Prośba', 'text-gray-400'], declined: ['Odrzucony', 'text-gray-600'] };

    function upsertParticipant(p) {
        EXP.participants = EXP.participants || [];
        const i = EXP.participants.findIndex(x => x.id === p.id || (x.user && p.user && x.user.id === p.user.id));
        if (i >= 0) { EXP.participants[i] = p; } else { EXP.participants.push(p); }
    }

    function participantRow(p) {
        const [label, cls] = statusLabels[p.status] || ['', 'text-gray-400'];
        const name = p.user ? p.user.name : 'Użytkownik';
        let actions = '';
        if (IS_LEADER) {
            if (p.status === 'requested') {
                actions = '<button class="text-xs text-green-400 font-semibold px-2" data-act="accept" data-pid="' + p.id + '">Akceptuj</button>' +
                          '<button class="text-xs text-red-400 font-semibold px-2" data-act="decline" data-pid="' + p.id + '">Odrzuć</button>';
            } else {
                actions = '<button class="text-xs text-red-400 font-semibold px-2" data-act="remove" data-pid="' + p.id + '">Usuń</button>';
            }
        }
        return '<div class="card flex items-center gap-3" style="padding:0.75rem">' +
            '<div class="w-9 h-9 rounded-full bg-surface-card flex items-center justify-center text-gray-300 flex-shrink-0">' + (name.charAt(0).toUpperCase()) + '</div>' +
            '<div class="flex-1 min-w-0"><p class="text-sm font-semibold text-white truncate">' + name + '</p>' +
            '<p class="text-xs ' + cls + '">' + label + '</p></div>' + actions + '</div>';
    }

    function renderParticipants() {
        const list = document.getElementById('participantsList');
        const parts = (EXP.participants || []).filter(p => p.status !== 'declined');
        if (!parts.length) {
            list.innerHTML = '<p class="text-gray-500 text-sm text-center py-6">Brak uczestników. ' + (IS_LEADER ? 'Zaproś kogoś lub udostępnij kod.' : '') + '</p>';
            return;
        }
        list.innerHTML = parts.map(participantRow).join('');
        list.querySelectorAll('[data-act]').forEach(btn => {
            btn.addEventListener('click', () => {
                const pid = parseInt(btn.dataset.pid, 10), act = btn.dataset.act;
                const method = act === 'remove' ? 'DELETE' : 'POST';
                const url = act === 'remove'
                    ? "/api/expeditions/" + EXP_ID + "/participants/" + pid
                    : "/api/expeditions/" + EXP_ID + "/participants/" + pid + "/" + act;
                btn.disabled = true;
                fetch(url, { method, headers: jsonHeaders })
                    .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
                    .then(({ ok, data }) => {
                        if (!ok) { alert(data.message || 'Błąd.'); btn.disabled = false; return; }
                        if (act === 'remove') {
                            EXP.participants = (EXP.participants || []).filter(p => p.id !== pid);
                        } else if (data && data.data) {
                            upsertParticipant(data.data);
                        } else if (data && data.id) {
                            upsertParticipant(data);
                        }
                        renderParticipants();
                    })
                    .catch(() => { alert('Błąd.'); btn.disabled = false; });
            });
        });
    }

    // --- Invite autocomplete (leader) ---
    const inviteInput = document.getElementById('inviteInput');
    if (inviteInput) {
        const ac = document.getElementById('inviteAutocomplete');
        let deb = null;
        inviteInput.addEventListener('input', function () {
            clearTimeout(deb);
            const q = this.value.trim();
            if (q.length < 2) { ac.style.display = 'none'; return; }
            deb = setTimeout(() => {
                fetch("{{ route('users.search') }}?q=" + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(users => {
                        ac.innerHTML = '';
                        if (!users.length) { ac.style.display = 'none'; return; }
                        users.forEach(u => {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            item.textContent = u.name;
                            item.addEventListener('click', () => invite(u.id));
                            ac.appendChild(item);
                        });
                        ac.style.display = 'block';
                    }).catch(() => { ac.style.display = 'none'; });
            }, 300);
        });
        function invite(userId) {
            ac.style.display = 'none';
            inviteInput.value = '';
            fetch("/api/expeditions/" + EXP_ID + "/participants", { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ user_id: userId }) })
                .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
                .then(({ ok, data }) => {
                    if (!ok) { alert(data.message || 'Nie udało się zaprosić.'); return; }
                    upsertParticipant(data.data || data);
                    renderParticipants();
                })
                .catch(() => alert('Błąd.'));
        }
    }

    // --- Findings (leader) ---
    function findingCard(f) {
        const photo = f.photo_url
            ? '<img src="' + (f.photo_thumb_url || f.photo_url) + '" style="width:64px;height:64px;object-fit:cover;border-radius:0.75rem;flex-shrink:0">'
            : '<div style="width:64px;height:64px;border-radius:0.75rem;background:#323248;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">📷</div>';
        const loc = [f.city, f.voivodeship].filter(Boolean).join(', ');
        const priv = f.is_private ? '<span class="text-xs text-purple-400 ml-1">🔒 prywatne</span>' : '';
        return '<div class="card flex gap-3 items-center" style="padding:0.75rem">' +
            '<a href="/findings/' + f.id + '" class="flex gap-3 items-center flex-1 min-w-0">' + photo +
            '<div class="flex-1 min-w-0">' +
            '<h3 class="font-bold text-white text-sm truncate">' + (f.name || '') + priv + '</h3>' +
            (loc ? '<p class="text-xs text-gray-400 mt-0.5 truncate">📍 ' + loc + '</p>' : '') +
            '<p class="text-xs text-gray-500 mt-0.5">📅 ' + (f.found_at || '') + '</p>' +
            (f.finder ? '<p class="text-xs text-amber-400 mt-0.5">' + f.finder.name + '</p>' : '') +
            '</div></a>' +
            '<button class="text-xs text-red-400 font-semibold px-2 flex-shrink-0" data-remove-finding="' + f.id + '">Usuń</button>' +
            '</div>';
    }

    function loadFindings() {
        const list = document.getElementById('findingsList');
        fetch("/api/expeditions/" + EXP_ID + "/findings", { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(res => {
                const items = res.data || [];
                if (!items.length) {
                    list.innerHTML = '<div class="flex flex-col items-center justify-center py-10 text-gray-500"><div class="text-4xl mb-2">⚒️</div><p class="text-sm">Uczestnicy nie przypięli jeszcze znalezisk.</p></div>';
                    return;
                }
                list.innerHTML = items.map(findingCard).join('');
                list.querySelectorAll('[data-remove-finding]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (!confirm('Usunąć to znalezisko z poszukiwania? Samo znalezisko zostanie zachowane w aplikacji.')) { return; }
                        const findingId = btn.dataset.removeFinding;
                        btn.disabled = true;
                        fetch("/api/expeditions/" + EXP_ID + "/findings/" + findingId, { method: 'DELETE', headers: jsonHeaders })
                            .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
                            .then(({ ok, data }) => {
                                if (!ok) { alert(data.message || 'Błąd.'); btn.disabled = false; return; }
                                btn.closest('.card').remove();
                            })
                            .catch(() => { alert('Błąd.'); btn.disabled = false; });
                    });
                });
            })
            .catch(() => { list.innerHTML = '<p class="text-red-400 text-sm text-center py-6">Błąd ładowania.</p>'; });
    }

    // --- Export findings to PDF (leader) ---
    // The export id survives navigation so a user can leave the page and come
    // back to a still-running job.
    const EXPORT_KEY = 'expedition_findings_export_' + EXP_ID;
    let exportPollTimer = null;

    function openExportModal() {
        document.getElementById('export-modal').classList.add('open');
        document.getElementById('export-percent').textContent = '0%';
        document.getElementById('export-bar').style.width = '0%';
        document.getElementById('export-message').textContent = 'Rozpoczynanie eksportu…';
        document.getElementById('export-download-btn').style.display = 'none';
        document.getElementById('export-hint').style.display = 'block';
    }

    function pollExportProgress(exportId) {
        clearInterval(exportPollTimer);
        exportPollTimer = setInterval(() => {
            fetch("/api/expeditions/" + EXP_ID + "/findings-export/" + exportId + "/progress", {
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
                    btn.href = "/api/expeditions/" + EXP_ID + "/findings-export/" + exportId + "/download";
                    btn.style.display = 'block';
                    btn.addEventListener('click', () => sessionStorage.removeItem(EXPORT_KEY), { once: true });
                    document.getElementById('export-hint').style.display = 'none';
                    document.getElementById('export-message').textContent = 'Twój PDF jest gotowy!';
                } else if (data.failed) {
                    clearInterval(exportPollTimer);
                    sessionStorage.removeItem(EXPORT_KEY);
                    document.getElementById('export-message').textContent = data.message || 'Eksport nie powiódł się.';
                }
            })
            .catch(() => {});
        }, 1000);
    }

    window.startExpeditionExport = function () {
        const existing = sessionStorage.getItem(EXPORT_KEY);
        if (existing) {
            openExportModal();
            pollExportProgress(existing);
            return;
        }

        openExportModal();

        fetch("/api/expeditions/" + EXP_ID + "/findings-export", {
            method: 'POST',
            headers: jsonHeaders,
        })
        .then(r => r.json())
        .then(data => {
            if (data.export_id) {
                sessionStorage.setItem(EXPORT_KEY, data.export_id);
                pollExportProgress(data.export_id);
            } else {
                document.getElementById('export-message').textContent = data.error || 'Nie udało się rozpocząć eksportu.';
            }
        })
        .catch(() => {
            document.getElementById('export-message').textContent = 'Błąd połączenia.';
        });
    };

    window.closeExpeditionExport = function () {
        clearInterval(exportPollTimer);
        document.getElementById('export-modal').classList.remove('open');
    };
})();
</script>
@endpush
