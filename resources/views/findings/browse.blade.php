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

    {{-- Bottom nav --}}
    <div class="nav-bar safe-bottom">
        <a href="{{ route('home') }}" class="nav-item">
            <span class="nav-icon">🏠</span><span>Start</span>
        </a>
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

    // Pre-fill user_id from URL (e.g. "Twoje znaleziska")
    const urlParams = new URLSearchParams(window.location.search);
    const initialUserId = urlParams.get('user_id');
    if (initialUserId) {
        selectedUserId = parseInt(initialUserId);
        // We'll resolve the name after loading
        const currentApiUser = @json(session('api_user'));
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

        return '<div class="card flex gap-3" style="padding:0.875rem">' +
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
            '<button class="like-btn flex flex-col items-center justify-center gap-0.5 flex-shrink-0" data-id="' + f.id + '" style="min-width:40px">' +
                '<span class="like-icon text-lg">' + heartEmoji + '</span>' +
                '<span class="like-count text-xs text-gray-400">' + (f.likes_count || 0) + '</span>' +
            '</button>' +
        '</div>';
    }

    function loadPage(page) {
        loading.style.display = 'flex';
        const cards = list.querySelectorAll('.card');
        cards.forEach(c => c.remove());
        const emptyMsg = list.querySelector('.empty-msg');
        if (emptyMsg) { emptyMsg.remove(); }

        fetch(buildUrl(page))
            .then(r => r.json())
            .then(res => {
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
            })
            .catch(() => {
                loading.style.display = 'none';
                const err = document.createElement('div');
                err.className = 'empty-msg flex items-center justify-center py-8 text-red-400 text-sm';
                err.textContent = 'Błąd ładowania znalezisk.';
                list.appendChild(err);
            });
    }

    // Like toggle
    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.like-btn');
        if (!btn) { return; }
        e.preventDefault();
        e.stopPropagation();

        const findingId = btn.dataset.id;
        const icon = btn.querySelector('.like-icon');
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
        })
        .catch(() => {});
    });

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
