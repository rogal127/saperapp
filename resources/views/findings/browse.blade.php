@extends('layouts.app')
@section('title', 'Przeglądaj znaleziska – Historius')

@push('styles')
<style>
    .autocomplete-wrap { position: relative; }
    .autocomplete-list {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
        background: #323248; border: 1px solid #404060; border-top: none;
        border-radius: 0 0 0.875rem 0.875rem; max-height: 200px; overflow-y: auto;
    }
    .autocomplete-item {
        padding: 0.5rem 0.75rem; font-size: 0.85rem; color: #e2e8f0;
        cursor: pointer;
    }
    .autocomplete-item:hover { background: #404060; }
    .user-tag {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f59e0b22; color: #f59e0b; border-radius: 0.5rem;
        padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 600;
    }
    .user-tag button { background: none; border: none; color: #f59e0b; cursor: pointer; font-size: 0.9rem; line-height: 1; padding: 0 2px; }

    .card-actions { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.625rem; margin-top: 0.125rem; border-top: 1px solid #363654; }
    .card-action-btn {
        flex: 1; text-align: center; text-decoration: none;
        padding: 0.4rem 0.4rem; background: rgba(245,158,11,0.08); border: none; color: #f0a840;
        border-radius: 0.5rem; cursor: pointer; font-size: 0.7rem; font-weight: 600;
    }

    /* Modal wiadomości */
    #message-modal {
        display: none; position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.75); align-items: flex-end; justify-content: center;
    }
    #message-modal.open { display: flex; }
    #message-sheet {
        background: #1a1a2e; border-radius: 1.25rem 1.25rem 0 0;
        border: 1px solid #2a2a3e; width: 100%; max-width: 480px;
        max-height: 90vh; display: flex; flex-direction: column;
        animation: slideUp 0.25s ease;
    }
    #message-sheet-header { flex-shrink: 0; padding: 1rem 1rem 0.75rem; border-bottom: 1px solid #2a2a3e; border-radius: 1.25rem 1.25rem 0 0; }
    #message-sheet-body { flex: 1; overflow-y: auto; }
    @keyframes slideUp { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-body { padding: 1rem; }
    .modal-title { font-weight: 700; font-size: 1rem; color: #fff; }
    .modal-close { color: #9ca3af; font-size: 1.4rem; background: none; border: none; cursor: pointer; line-height: 1; }
    .modal-textarea {
        width: 100%; background: #2a2a3e; border: 1px solid #404060;
        border-radius: 0.75rem; color: #fff; padding: 0.75rem;
        font-size: 0.82rem; resize: none; outline: none;
        box-sizing: border-box; font-family: inherit;
    }
    .modal-textarea:focus { border-color: #f59e0b; }
    .modal-send-btn {
        margin-top: 0.75rem; width: 100%; padding: 0.8rem;
        background: #f59e0b; color: #1a1a2e; font-weight: 700;
        border: none; border-radius: 0.75rem; cursor: pointer;
        font-size: 0.9rem; transition: opacity 0.15s;
    }
    .modal-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    #modal-status { text-align: center; margin-top: 0.5rem; font-size: 0.78rem; min-height: 1.1em; }

    #shareOverlay .autocomplete-list { position: static; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('home') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 id="pageTitle" class="text-lg font-bold text-white flex-1 truncate">Przeglądaj znaleziska</h1>
    </div>

    {{-- Filtry --}}
    <div class="px-4 pt-3 pb-2 flex flex-col gap-2 border-b border-surface-card flex-shrink-0">
        <div class="flex gap-2">
            <input type="text" id="filterName" class="input-field text-sm flex-1" placeholder="Nazwa znaleziska..." style="padding:0.6rem 0.75rem">
            <select id="filterSort" class="input-field text-sm" style="padding:0.6rem 0.75rem;width:auto">
                <option value="newest">Najnowsze</option>
                <option value="oldest">Najstarsze</option>
                <option value="most_liked">Najpopularniejsze</option>
            </select>
        </div>
        <div class="flex gap-2">
            <select id="filterVoivodeship" class="input-field text-sm flex-1" style="padding:0.6rem 0.75rem">
                <option value="">Województwo</option>
            </select>
            <input type="text" id="filterCity" class="input-field text-sm flex-1" placeholder="Miasto..." style="padding:0.6rem 0.75rem">
        </div>
        <div class="flex gap-2 items-center">
            <div class="autocomplete-wrap flex-1">
                <input type="text" id="filterUserInput" class="input-field text-sm" placeholder="Użytkownik..." style="padding:0.6rem 0.75rem;width:100%">
                <div id="userAutocomplete" class="autocomplete-list" style="display:none"></div>
            </div>
            <div id="userTag" style="display:none"></div>
        </div>
    </div>

    {{-- Lista znalezisk --}}
    <div id="findingsList" class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-3">
        <div id="loadingIndicator" class="flex items-center justify-center py-8">
            <div class="text-gray-400 text-sm">Ładowanie...</div>
        </div>
    </div>

    {{-- Paginacja --}}
    <div id="pagination" class="px-4 pb-2 pt-1 border-t border-surface-card flex-shrink-0 hidden">
        <div class="flex items-center justify-between">
            <button id="prevPage" class="btn-secondary text-sm" style="padding:0.5rem 1rem;width:auto" disabled>Poprzednia</button>
            <span id="pageInfo" class="text-sm text-gray-400"></span>
            <button id="nextPage" class="btn-secondary text-sm" style="padding:0.5rem 1rem;width:auto" disabled>Następna</button>
        </div>
    </div>

    {{-- Bottom sheet: lista polubień --}}
    <div id="likersOverlay" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,0.5)">
        <div id="likersSheet" class="fixed bottom-0 left-0 right-0 bg-surface rounded-t-2xl max-h-[60vh] flex flex-col transform translate-y-full transition-transform duration-300 ease-out" style="background:#1a1a2e">
            <div class="flex items-center justify-between px-4 pt-4 pb-2 border-b border-surface-card flex-shrink-0">
                <h3 class="text-base font-bold text-white">Polubienia</h3>
                <button id="closeLikersBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-surface-card text-gray-400 text-lg">&times;</button>
            </div>
            <div id="likersList" class="flex-1 overflow-y-auto px-4 py-3"></div>
        </div>
    </div>

    {{-- Modal wiadomości do znalazcy --}}
    <div id="message-modal" onclick="handleMessageBackdrop(event)">
        <div id="message-sheet">
            <div id="message-sheet-header">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div class="modal-title" id="msg-modal-title">Napisz wiadomość</div>
                    <button class="modal-close" onclick="closeMessageModal()">✕</button>
                </div>
            </div>
            <div id="message-sheet-body">
                <div class="modal-body">
                    <textarea id="modal-msg" class="modal-textarea" rows="3" placeholder="Napisz wiadomość do znalazcy…"></textarea>
                    <button id="modal-send" class="modal-send-btn" onclick="sendModalMessage()">Wyślij wiadomość</button>
                    <div id="modal-status"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom sheet: prześlij znajomemu --}}
    <div id="shareOverlay" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,0.5)">
        <div id="shareSheet" class="fixed bottom-0 left-0 right-0 bg-surface rounded-t-2xl max-h-[80vh] flex flex-col transform translate-y-full transition-transform duration-300 ease-out" style="background:#1a1a2e">
            <div class="flex items-center justify-between px-4 pt-4 pb-2 border-b border-surface-card flex-shrink-0">
                <h3 class="text-base font-bold text-white">Prześlij znajomemu</h3>
                <button id="closeShareBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-surface-card text-gray-400 text-lg">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-3">
                <div class="relative" id="shareSearchWrap">
                    <input type="text" id="shareUserInput" class="input-field text-sm" placeholder="🔍 Wpisz nick znajomego..." autocomplete="off">
                    <div id="shareAutocomplete" class="autocomplete-list" style="display:none"></div>
                </div>

                <div id="shareSelectedUser" class="hidden items-center gap-2 bg-surface-card rounded-xl p-3">
                    <div class="w-8 h-8 rounded-full bg-[#404060] flex items-center justify-center text-xs font-bold overflow-hidden flex-shrink-0 text-gray-300" id="shareSelectedAvatar"></div>
                    <span class="text-sm font-semibold text-white flex-1" id="shareSelectedName"></span>
                    <button id="shareClearUserBtn" class="text-gray-500 text-lg px-2">&times;</button>
                </div>

                <textarea id="shareMessageBody" rows="2" maxlength="2000"
                          placeholder="Dodaj wiadomość (opcjonalnie)..."
                          class="w-full bg-transparent border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 resize-none focus:outline-none focus:border-amber-500"></textarea>

                <div id="shareError" class="text-red-400 text-xs" style="display:none"></div>

                <button id="shareSendBtn"
                        class="px-4 py-2.5 rounded-xl bg-amber-500 text-black text-sm font-semibold active:opacity-80 disabled:opacity-40"
                        disabled>
                    Wyślij
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const list = document.getElementById('findingsList');
    const loading = document.getElementById('loadingIndicator');
    const pagination = document.getElementById('pagination');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');
    const pageTitle = document.getElementById('pageTitle');

    const filterName = document.getElementById('filterName');
    const filterVoivodeship = document.getElementById('filterVoivodeship');
    const filterSort = document.getElementById('filterSort');
    const filterCity = document.getElementById('filterCity');
    const filterUserInput = document.getElementById('filterUserInput');
    const userAutocomplete = document.getElementById('userAutocomplete');
    const userTag = document.getElementById('userTag');

    let currentPage = 1;
    let debounceTimer = null;
    let userDebounce = null;
    let selectedUserId = null;
    let selectedUserName = null;
    const currentApiUser = @json(session('api_user'));
    const currentUserId = currentApiUser ? currentApiUser.id : null;

    const MESSAGE_BASE = "{{ url('/api/findings') }}";
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    let activeMessageFindingId = null;

    function cacheKey(page) {
        const p = new URLSearchParams();
        p.set('p', page);
        if (selectedUserId) p.set('u', selectedUserId);
        if (filterName.value.trim()) p.set('n', filterName.value.trim());
        if (filterVoivodeship.value) p.set('v', filterVoivodeship.value);
        if (filterCity.value.trim()) p.set('c', filterCity.value.trim());
        if (filterSort.value && filterSort.value !== 'newest') p.set('s', filterSort.value);
        return 'browse:' + p.toString();
    }

    function getCache(key) {
        try { return JSON.parse(sessionStorage.getItem(key)); } catch { return null; }
    }

    function setCache(key, data) {
        try {
            const keys = Object.keys(sessionStorage).filter(k => k.startsWith('browse:'));
            if (keys.length > 30) {
                keys.slice(0, keys.length - 15).forEach(k => sessionStorage.removeItem(k));
            }
            sessionStorage.setItem(key, JSON.stringify(data));
        } catch {}
    }

    function updateCachedLike(findingId, isLiked, likesCount) {
        try {
            Object.keys(sessionStorage).filter(k => k.startsWith('browse:')).forEach(key => {
                const cached = JSON.parse(sessionStorage.getItem(key));
                if (!cached || !cached.data) return;
                const f = cached.data.find(x => x.id == findingId);
                if (f) {
                    f.is_liked = isLiked;
                    f.likes_count = likesCount;
                    sessionStorage.setItem(key, JSON.stringify(cached));
                }
            });
        } catch {}
    }

    // Pre-fill user_id from URL (e.g. "Twoje znaleziska")
    const urlParams = new URLSearchParams(window.location.search);
    const initialUserId = urlParams.get('user_id');
    if (initialUserId) {
        selectedUserId = parseInt(initialUserId);
        if (currentApiUser && currentApiUser.id == selectedUserId) {
            selectedUserName = currentApiUser.name;
            pageTitle.textContent = 'Twoje znaleziska';
        }
        showUserTag();
    }

    fetch("{{ route('voivodeships.index') }}")
        .then(r => r.json())
        .then(voivodeships => {
            voivodeships.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                filterVoivodeship.appendChild(opt);
            });
        })
        .catch(() => {});

    function buildUrl(page) {
        const params = new URLSearchParams();
        params.set('page', page);
        if (selectedUserId) { params.set('user_id', selectedUserId); }
        if (filterName.value.trim()) { params.set('name', filterName.value.trim()); }
        if (filterVoivodeship.value) { params.set('voivodeship', filterVoivodeship.value); }
        if (filterCity.value.trim()) { params.set('city', filterCity.value.trim()); }
        if (filterSort.value) { params.set('sort', filterSort.value); }
        return "{{ route('findings.browse.api') }}?" + params.toString();
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML.replace(/'/g, '&#39;');
    }

    function typeBadgeHtml(type) {
        const map = {
            archaeological_monument: { label: 'Zabytek archeologiczny', bg: 'rgba(239,68,68,0.15)', color: '#f87171' },
            monument: { label: 'Zabytek', bg: 'rgba(250,204,21,0.15)', color: '#fde047' },
            non_monument: { label: 'Przedmiot niezabytkowy', bg: 'rgba(34,197,94,0.15)', color: '#4ade80' },
        };
        const badge = map[type];
        if (!badge) { return ''; }
        return '<span style="display:inline-flex;align-items:center;gap:4px;background:' + badge.bg + ';color:' + badge.color + ';border-radius:0.5rem;padding:0.15rem 0.5rem;font-size:0.65rem;font-weight:600"><span style="width:6px;height:6px;border-radius:50%;background:currentColor"></span>' + badge.label + '</span>';
    }

    function renderCard(f) {
        const photoHtml = f.photo_url
            ? '<img src="' + f.photo_url + '" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:0.75rem;flex-shrink:0">'
            : '<div style="width:72px;height:72px;border-radius:0.75rem;background:#323248;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">📷</div>';

        const locationParts = [];
        if (f.city) { locationParts.push(f.city); }
        if (f.voivodeship) { locationParts.push(f.voivodeship); }
        const location = locationParts.join(', ');

        const heartEmoji = f.is_liked ? '❤️' : '🤍';

        const isOwner = currentUserId && f.finder && f.finder.id === currentUserId;
        const editBtnHtml = isOwner
            ? '<a href="/findings/' + f.id + '/edit?from=browse" class="flex items-center justify-center flex-shrink-0 text-gray-400 hover:text-amber-400" style="min-width:32px;font-size:1.1rem" title="Edytuj">✏️</a>'
            : '';

        const messageBtnHtml = !isOwner
            ? '<button class="card-action-btn" onclick="openMessageModal(' + f.id + ', \'' + escHtml(f.name) + '\')">✉️ Wiadomość</button>'
            : '';

        const shareBtnHtml = '<button class="card-action-btn" onclick="openShareSheet(' + f.id + ', \'' + escHtml(f.name) + '\')">📨 Prześlij</button>';

        return '<div class="card flex flex-col gap-0" style="padding:0.875rem">' +
            '<div class="flex gap-3 items-center">' +
                '<a href="/findings/' + f.id + '" class="flex gap-3 flex-1 min-w-0">' +
                    photoHtml +
                    '<div class="flex-1 min-w-0">' +
                        '<h3 class="font-bold text-white text-sm truncate">' + (f.name || '') + '</h3>' +
                        (f.type ? '<div class="mt-1">' + typeBadgeHtml(f.type) + '</div>' : '') +
                        (location ? '<p class="text-xs text-gray-400 mt-1 truncate">📍 ' + location + '</p>' : '') +
                        '<p class="text-xs text-gray-500 mt-0.5">📅 ' + (f.found_at || '') + '</p>' +
                        (f.finder ? '<p class="text-xs text-amber-400 mt-0.5">' + f.finder.name + '</p>' : '') +
                    '</div>' +
                '</a>' +
                editBtnHtml +
                '<button class="like-btn flex flex-col items-center justify-center gap-0.5 flex-shrink-0" data-id="' + f.id + '" style="min-width:48px;min-height:44px;padding:0.5rem">' +
                    '<span class="like-icon text-lg">' + heartEmoji + '</span>' +
                    '<span class="like-count text-sm ' + (f.likes_count > 0 ? 'text-gray-300 underline underline-offset-2 cursor-pointer' : 'text-gray-400') + '">' + (f.likes_count || 0) + '</span>' +
                '</button>' +
            '</div>' +
            '<div class="card-actions">' +
                '<a href="/findings/' + f.id + '#comments" class="card-action-btn">💬 Skomentuj</a>' +
                messageBtnHtml +
                shareBtnHtml +
            '</div>' +
        '</div>';
    }

    function clearList() {
        list.querySelectorAll('.card').forEach(c => c.remove());
        list.querySelectorAll('.empty-msg,.error-msg').forEach(m => m.remove());
    }

    function renderResults(res, page) {
        clearList();
        loading.style.display = 'none';

        const findings = res.data || [];

        if (findings.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'empty-msg flex flex-col items-center justify-center py-12 text-gray-500';
            empty.innerHTML = '<div class="text-4xl mb-3">🔍</div><p class="text-sm">Brak znalezisk spełniających kryteria</p>';
            list.appendChild(empty);
            pagination.classList.add('hidden');
            return;
        }

        findings.forEach(f => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = renderCard(f);
            list.appendChild(wrapper.firstChild);
        });

        const meta = res.meta || res;
        const lastPage = meta.last_page || 1;
        currentPage = meta.current_page || page;

        if (lastPage > 1) {
            pagination.classList.remove('hidden');
            pageInfo.textContent = currentPage + ' / ' + lastPage;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= lastPage;
        } else {
            pagination.classList.add('hidden');
        }
    }

    function loadPage(page) {
        const key = cacheKey(page);
        const cached = getCache(key);

        if (cached) {
            renderResults(cached, page);
        } else {
            clearList();
            loading.style.display = 'flex';
        }

        fetch(buildUrl(page))
            .then(r => r.json())
            .then(res => {
                setCache(key, res);
                renderResults(res, page);
            })
            .catch(() => {
                if (!cached) {
                    loading.style.display = 'none';
                    const err = document.createElement('div');
                    err.className = 'error-msg flex items-center justify-center py-8 text-red-400 text-sm';
                    err.textContent = 'Błąd ładowania znalezisk.';
                    list.appendChild(err);
                }
            });
    }

    // Like toggle (click on heart icon)
    list.addEventListener('click', function (e) {
        const icon = e.target.closest('.like-icon');
        if (!icon) { return; }
        e.preventDefault();
        e.stopPropagation();

        const btn = icon.closest('.like-btn');
        const findingId = btn.dataset.id;
        const count = btn.querySelector('.like-count');

        fetch('/api/findings/' + findingId + '/like', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            icon.textContent = data.is_liked ? '❤️' : '🤍';
            count.textContent = data.likes_count;
            if (data.likes_count > 0) {
                count.classList.add('text-gray-300', 'underline', 'underline-offset-2', 'cursor-pointer');
                count.classList.remove('text-gray-400');
            } else {
                count.classList.remove('text-gray-300', 'underline', 'underline-offset-2', 'cursor-pointer');
                count.classList.add('text-gray-400');
            }
            updateCachedLike(findingId, data.is_liked, data.likes_count);
        })
        .catch(() => {});
    });

    // Show likers (click on count)
    list.addEventListener('click', function (e) {
        const count = e.target.closest('.like-count');
        if (!count) { return; }
        const btn = count.closest('.like-btn');
        const likesCount = parseInt(count.textContent);
        if (!likesCount) { return; }
        e.preventDefault();
        e.stopPropagation();
        openLikersSheet(btn.dataset.id);
    });

    function openLikersSheet(findingId) {
        const overlay = document.getElementById('likersOverlay');
        const sheet = document.getElementById('likersSheet');
        const likersList = document.getElementById('likersList');

        overlay.classList.remove('hidden');
        requestAnimationFrame(() => {
            sheet.classList.remove('translate-y-full');
            sheet.classList.add('translate-y-0');
        });

        likersList.innerHTML = '<div class="text-center text-gray-500 text-sm py-4">Ładowanie...</div>';

        fetch('/api/findings/' + findingId + '/likes', {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(users => {
            if (!users.length) {
                likersList.innerHTML = '<div class="text-center text-gray-500 text-sm py-4">Brak polubień</div>';
                return;
            }
            likersList.innerHTML = users.map(u => {
                const avatar = u.avatar_url
                    ? '<img src="' + u.avatar_url + '" alt="" style="width:100%;height:100%;object-fit:cover">'
                    : '<span class="text-xs font-bold">' + (u.name ? u.name.charAt(0).toUpperCase() : '?') + '</span>';
                return '<a href="/users/' + u.id + '" class="flex items-center gap-3 py-2">'
                    + '<div class="w-9 h-9 rounded-full bg-surface-card flex items-center justify-center overflow-hidden flex-shrink-0 text-gray-300">' + avatar + '</div>'
                    + '<span class="text-sm font-semibold text-white">' + (u.name || 'Anonim') + '</span>'
                    + '</a>';
            }).join('');
        })
        .catch(() => {
            likersList.innerHTML = '<div class="text-center text-red-400 text-sm py-4">Błąd ładowania</div>';
        });
    }

    function closeLikersSheet() {
        const overlay = document.getElementById('likersOverlay');
        const sheet = document.getElementById('likersSheet');
        sheet.classList.remove('translate-y-0');
        sheet.classList.add('translate-y-full');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }

    document.getElementById('closeLikersBtn').addEventListener('click', closeLikersSheet);
    document.getElementById('likersOverlay').addEventListener('click', function (e) {
        if (e.target === this) { closeLikersSheet(); }
    });

    // Napisz wiadomość do znalazcy
    window.openMessageModal = function (findingId, findingName) {
        activeMessageFindingId = findingId;
        document.getElementById('msg-modal-title').textContent = 'Wiadomość o: ' + findingName;
        document.getElementById('modal-msg').value = '';
        document.getElementById('modal-status').textContent = '';
        document.getElementById('modal-status').style.color = '';
        document.getElementById('modal-send').disabled = false;
        document.getElementById('message-modal').classList.add('open');
    };

    window.closeMessageModal = function () {
        document.getElementById('message-modal').classList.remove('open');
        activeMessageFindingId = null;
    };

    window.handleMessageBackdrop = function (e) {
        if (e.target === document.getElementById('message-modal')) { closeMessageModal(); }
    };

    window.sendModalMessage = function () {
        const body = document.getElementById('modal-msg').value.trim();
        if (!body || !activeMessageFindingId) { return; }

        const btn = document.getElementById('modal-send');
        const status = document.getElementById('modal-status');
        btn.disabled = true;
        status.textContent = 'Wysyłanie…';
        status.style.color = '#9ca3af';

        fetch(MESSAGE_BASE + '/' + activeMessageFindingId + '/message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ body: body }),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data: data })))
        .then(({ ok, data }) => {
            if (ok) {
                status.textContent = '✓ Wiadomość wysłana!';
                status.style.color = '#34d399';
                document.getElementById('modal-msg').value = '';
                setTimeout(closeMessageModal, 1500);
            } else {
                status.textContent = data.message || 'Nie udało się wysłać.';
                status.style.color = '#f87171';
                btn.disabled = false;
            }
        })
        .catch(() => {
            status.textContent = 'Błąd połączenia.';
            status.style.color = '#f87171';
            btn.disabled = false;
        });
    };

    // Prześlij znajomemu
    (function () {
        const overlay = document.getElementById('shareOverlay');
        const sheet = document.getElementById('shareSheet');
        const closeBtn = document.getElementById('closeShareBtn');
        const input = document.getElementById('shareUserInput');
        const ac = document.getElementById('shareAutocomplete');
        const selectedWrap = document.getElementById('shareSelectedUser');
        const selectedAvatar = document.getElementById('shareSelectedAvatar');
        const selectedName = document.getElementById('shareSelectedName');
        const clearUserBtn = document.getElementById('shareClearUserBtn');
        const bodyInput = document.getElementById('shareMessageBody');
        const sendBtn = document.getElementById('shareSendBtn');
        const errorEl = document.getElementById('shareError');

        let activeShareFindingId = null;
        let activeShareFindingName = '';
        let selectedUser = null;
        let deb = null;

        window.openShareSheet = function (findingId, findingName) {
            activeShareFindingId = findingId;
            activeShareFindingName = findingName;
            selectedUser = null;
            input.value = '';
            bodyInput.value = '';
            errorEl.style.display = 'none';
            selectedWrap.classList.add('hidden');
            selectedWrap.classList.remove('flex');
            sendBtn.disabled = true;
            sendBtn.textContent = 'Wyślij';

            overlay.classList.remove('hidden');
            requestAnimationFrame(() => {
                sheet.classList.remove('translate-y-full');
                sheet.classList.add('translate-y-0');
            });
            setTimeout(() => input.focus(), 300);
        };

        function closeShareSheet() {
            sheet.classList.remove('translate-y-0');
            sheet.classList.add('translate-y-full');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }

        function selectUser(u) {
            selectedUser = u;
            ac.style.display = 'none';
            input.value = '';
            selectedWrap.classList.remove('hidden');
            selectedWrap.classList.add('flex');
            selectedAvatar.innerHTML = u.avatar_url
                ? '<img src="' + u.avatar_url + '" alt="" style="width:100%;height:100%;object-fit:cover">'
                : (u.name ? u.name.charAt(0).toUpperCase() : '?');
            selectedName.textContent = u.name;
            sendBtn.disabled = false;
        }

        function clearSelectedUser() {
            selectedUser = null;
            selectedWrap.classList.add('hidden');
            selectedWrap.classList.remove('flex');
            sendBtn.disabled = true;
        }

        closeBtn.addEventListener('click', closeShareSheet);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) { closeShareSheet(); }
        });
        clearUserBtn.addEventListener('click', clearSelectedUser);

        input.addEventListener('input', function () {
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
                            item.addEventListener('click', () => selectUser(u));
                            ac.appendChild(item);
                        });
                        ac.style.display = 'block';
                    }).catch(() => { ac.style.display = 'none'; });
            }, 300);
        });

        sendBtn.addEventListener('click', function () {
            if (!selectedUser || !activeShareFindingId) { return; }

            const body = bodyInput.value.trim() || ('Polecam Ci to znalezisko: ' + activeShareFindingName);
            errorEl.style.display = 'none';
            sendBtn.disabled = true;
            sendBtn.textContent = 'Wysyłanie...';

            fetch(MESSAGE_BASE + '/' + activeShareFindingId + '/message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ body: body, user_id: selectedUser.id }),
            })
            .then(r => {
                if (!r.ok) { return r.json().then(d => Promise.reject(d)); }
                return r.json();
            })
            .then(data => {
                window.location.href = "{{ url('/messages') }}/" + data.conversation_id;
            })
            .catch(err => {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Wyślij';
                errorEl.textContent = err.message || 'Nie udało się wysłać wiadomości.';
                errorEl.style.display = 'block';
            });
        });
    })();

    // User autocomplete
    filterUserInput.addEventListener('input', function () {
        clearTimeout(userDebounce);
        const q = this.value.trim();
        if (q.length < 2) {
            userAutocomplete.style.display = 'none';
            return;
        }
        userDebounce = setTimeout(() => {
            fetch("{{ route('users.search') }}?q=" + encodeURIComponent(q))
                .then(r => r.json())
                .then(users => {
                    userAutocomplete.innerHTML = '';
                    if (!users.length) {
                        userAutocomplete.style.display = 'none';
                        return;
                    }
                    users.forEach(u => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = u.name;
                        item.addEventListener('click', () => selectUser(u.id, u.name));
                        userAutocomplete.appendChild(item);
                    });
                    userAutocomplete.style.display = 'block';
                })
                .catch(() => { userAutocomplete.style.display = 'none'; });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.autocomplete-wrap')) {
            userAutocomplete.style.display = 'none';
        }
    });

    function selectUser(id, name) {
        selectedUserId = id;
        selectedUserName = name;
        filterUserInput.value = '';
        userAutocomplete.style.display = 'none';
        showUserTag();
        onFilterChange();
    }

    function showUserTag() {
        const name = selectedUserName || ('Użytkownik #' + selectedUserId);
        userTag.innerHTML = '<span class="user-tag">👤 ' + name + ' <button onclick="clearUserFilter()">✕</button></span>';
        userTag.style.display = 'block';
        filterUserInput.style.display = 'none';
    }

    window.clearUserFilter = function () {
        selectedUserId = null;
        selectedUserName = null;
        userTag.style.display = 'none';
        userTag.innerHTML = '';
        filterUserInput.style.display = 'block';
        pageTitle.textContent = 'Przeglądaj znaleziska';
        onFilterChange();
    };

    // Filter change handlers
    function onFilterChange() {
        currentPage = 1;
        loadPage(1);
    }

    filterVoivodeship.addEventListener('change', onFilterChange);
    filterSort.addEventListener('change', onFilterChange);

    filterCity.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(onFilterChange, 400);
    });
    filterName.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(onFilterChange, 400);
    });

    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) { loadPage(currentPage - 1); }
    });
    nextBtn.addEventListener('click', function () {
        loadPage(currentPage + 1);
    });

    loadPage(1);
})();
</script>
@endpush
