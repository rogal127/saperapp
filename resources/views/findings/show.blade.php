@extends('layouts.app')
@section('title', ($finding['name'] ?? 'Znalezisko') . ' – szczegóły')

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</button>
        <h1 class="text-lg font-bold text-white flex-1 truncate">Szczegóły znaleziska</h1>
    </div>

    <div class="flex-1 overflow-y-auto">

        {{-- Zdjęcia --}}
        @php
            $photos = $finding['photos'] ?? [];
            if (empty($photos) && !empty($finding['photo_url'])) {
                $photos = [['url' => $finding['photo_url']]];
            }
        @endphp
        @if(!empty($photos))
        <div style="background:#0d0d1a;position:relative">
            <div id="photoCarousel" style="display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none">
                @foreach($photos as $photo)
                @php
                    $photoPrivate = ! empty($photo['is_private']);
                    $photoSrc = ($photoPrivate && ! empty($photo['id']))
                        ? route('findings.photo', [$finding['id'], $photo['id']])
                        : $photo['url'];
                @endphp
                <div style="flex:0 0 100%;scroll-snap-align:center;position:relative">
                    <img src="{{ $photoSrc }}" alt=""
                         style="width:100%;max-height:55vh;object-fit:contain;cursor:pointer"
                         onclick="openLightbox(this.src)">
                    @if($photoPrivate)
                    <span style="position:absolute;bottom:10px;right:10px;background:#a855f7;color:#fff;font-size:0.7rem;font-weight:700;padding:0.15rem 0.5rem;border-radius:0.5rem">🔒 Prywatne</span>
                    @endif
                </div>
                @endforeach
            </div>
            @if(count($photos) > 1)
            <div id="photoCounter"
                 style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);color:#fff;font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:1rem">
                1 / {{ count($photos) }}
            </div>
            <div style="position:absolute;bottom:10px;left:0;right:0;display:flex;justify-content:center;gap:6px;pointer-events:none">
                @foreach($photos as $i => $photo)
                <span class="carousel-dot" data-index="{{ $i }}"
                      style="width:7px;height:7px;border-radius:50%;background:{{ $i === 0 ? '#f59e0b' : 'rgba(255,255,255,0.4)' }};transition:background 0.2s"></span>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <div class="px-4 py-5 flex flex-col gap-3">

            {{-- Nazwa --}}
            <h2 class="text-xl font-bold text-white">{{ $finding['name'] }}</h2>

            @if(!empty($finding['is_private']))
                <span class="inline-flex items-center gap-1 self-start px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 text-xs font-semibold">
                    🙈 Prywatne — widoczne tylko dla Ciebie
                </span>
            @endif

            {{-- Właściciel --}}
            @if(!empty($finding['finder']))
                @php $finder = $finding['finder']; @endphp
                <a href="{{ route('users.show', $finder['id']) }}"
                   class="flex items-center gap-2 text-sm text-amber-400 font-semibold">
                    <div class="w-7 h-7 rounded-full bg-surface-card flex items-center justify-center text-xs font-bold overflow-hidden flex-shrink-0">
                        @if(!empty($finder['avatar_url']))
                            <img src="{{ $finder['avatar_url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
                        @else
                            {{ strtoupper(substr($finder['name'] ?? '?', 0, 1)) }}
                        @endif
                    </div>
                    {{ $finder['name'] }}
                </a>
            @endif

            {{-- Type badge --}}
            @if(!empty($finding['type']))
            @php
                $typeBadge = match($finding['type']) {
                    'archaeological_monument' => ['label' => 'Zabytek archeologiczny', 'style' => 'background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3)'],
                    'monument'                => ['label' => 'Zabytek',                'style' => 'background:rgba(250,204,21,0.15);color:#fde047;border:1px solid rgba(250,204,21,0.3)'],
                    'non_monument'            => ['label' => 'Przedmiot niezabytkowy', 'style' => 'background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3)'],
                    default => null,
                };
            @endphp
            @if($typeBadge)
            <div style="display:inline-flex;align-items:center;gap:0.375rem;{{ $typeBadge['style'] }};border-radius:0.75rem;padding:0.3rem 0.75rem;font-size:0.75rem;font-weight:600;width:fit-content">
                <span style="width:8px;height:8px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                {{ $typeBadge['label'] }}
            </div>
            @endif
            @endif

            {{-- Meta --}}
            <div class="flex flex-col gap-1">
                <div class="text-sm text-gray-400">📏 {{ $finding['depth_cm'] }} cm głębokości</div>
                <div class="text-sm text-gray-400">📅 {{ $finding['found_at'] }}{{ !empty($finding['city']) ? ' · ' . $finding['city'] : '' }}</div>
                @if(!empty($finding['voivodeship']))
                    <div class="text-sm text-gray-400">🗺️ {{ $finding['voivodeship'] }}</div>
                @endif
            </div>

            {{-- Polubienia --}}
            <div class="flex items-center gap-3">
                <button id="likeBtn" data-id="{{ $finding['id'] }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all active:scale-95"
                        style="background:{{ !empty($finding['is_liked']) ? 'rgba(239,68,68,0.15)' : '#323248' }}">
                    <span id="likeIcon" class="text-lg">{{ !empty($finding['is_liked']) ? '❤️' : '🤍' }}</span>
                    <span id="likeCount" class="text-sm font-semibold {{ !empty($finding['is_liked']) ? 'text-red-400' : 'text-gray-400' }}">{{ $finding['likes_count'] ?? 0 }}</span>
                </button>
                @if(($finding['likes_count'] ?? 0) > 0)
                <button id="showLikersBtn" class="text-sm text-gray-400 underline underline-offset-2 active:text-gray-300 px-3 py-2 -mx-1 rounded-lg">
                    kto polubił?
                </button>
                @endif
            </div>

            {{-- Bottom sheet: lista polubień --}}
            <div id="likersOverlay" class="fixed inset-0 z-50 hidden" style="background:rgba(0,0,0,0.5)">
                <div id="likersSheet" class="fixed bottom-0 left-0 right-0 bg-surface rounded-t-2xl max-h-[60vh] flex flex-col transform translate-y-full transition-transform duration-300 ease-out" style="background:#1a1a2e">
                    <div class="flex items-center justify-between px-4 pt-4 pb-2 border-b border-surface-card flex-shrink-0">
                        <h3 class="text-base font-bold text-white">Polubienia</h3>
                        <button id="closeLikersBtn" class="w-8 h-8 flex items-center justify-center rounded-full bg-surface-card text-gray-400 text-lg">&times;</button>
                    </div>
                    <div id="likersList" class="flex-1 overflow-y-auto px-4 py-3">
                        <div id="likersLoading" class="text-center text-gray-500 text-sm py-4">Ładowanie...</div>
                    </div>
                </div>
            </div>

            {{-- Opis --}}
            @if(!empty($finding['description']))
            <div class="bg-surface-card rounded-xl p-4 text-sm text-gray-300 leading-relaxed">
                {{ $finding['description'] }}
            </div>
            @endif

            {{-- Sprawozdanie (tylko właściciel) --}}
            @if(!empty($finding['report_url']))
            <a href="{{ $finding['report_url'] }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 bg-surface-card rounded-xl p-4 active:opacity-80">
                <span class="text-2xl">📄</span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-semibold text-white">Sprawozdanie</span>
                    <span class="block text-xs text-gray-500">Dokument PDF — widoczny tylko dla Ciebie</span>
                </span>
                <span class="text-amber-400 text-sm font-semibold whitespace-nowrap">Pobierz</span>
            </a>
            @endif

            @if(!empty($finding['can_view_exact_location']) && isset($finding['latitude'], $finding['longitude']))
            <a href="https://www.google.com/maps?q={{ $finding['latitude'] }},{{ $finding['longitude'] }}"
               target="_blank" rel="noopener"
               class="flex items-center gap-3 bg-surface-card rounded-xl p-4 active:opacity-80">
                <span class="text-2xl">📍</span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-semibold text-white">Dokładna lokalizacja</span>
                    <span class="block text-xs text-gray-500">{{ $finding['latitude'] }}, {{ $finding['longitude'] }}</span>
                </span>
                <span class="text-amber-400 text-sm font-semibold whitespace-nowrap">Mapa</span>
            </a>
            @else
            <div class="text-xs text-gray-600">📌 Dokładna lokalizacja znaleziska jest chroniona</div>
            @endif

            {{-- Komentarze --}}
            <div class="mt-4">
                <h3 class="text-base font-bold text-white mb-3">Komentarze</h3>

                {{-- Formularz dodawania --}}
                <div class="bg-surface-card rounded-xl p-4 mb-4">
                    <div id="commentForm">
                        <textarea id="commentBody" rows="2" maxlength="2000"
                                  placeholder="Napisz komentarz..."
                                  class="w-full bg-transparent border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 resize-none focus:outline-none focus:border-amber-500"></textarea>

                        <div id="commentPhotosPreview" class="flex gap-2 mt-2 flex-wrap" style="display:none"></div>

                        <div class="flex items-center justify-between mt-2">
                            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer active:text-gray-300">
                                <input type="file" id="commentPhotosInput" accept="image/*" multiple
                                       style="display:none" onchange="previewCommentPhotos(this)">
                                <span class="text-lg">📷</span>
                                <span id="commentPhotoCount"></span>
                            </label>
                            <button onclick="submitComment()" id="commentSubmitBtn"
                                    class="px-4 py-1.5 rounded-lg bg-amber-500 text-black text-sm font-semibold active:opacity-80 disabled:opacity-40"
                                    disabled>
                                Wyślij
                            </button>
                        </div>
                        <div id="commentError" class="text-red-400 text-xs mt-1" style="display:none"></div>
                    </div>
                </div>

                {{-- Lista komentarzy --}}
                <div id="commentsList"></div>
                <div id="commentsLoading" class="text-center text-gray-500 text-sm py-2">Ładowanie komentarzy...</div>
                <button id="loadMoreComments" class="w-full text-center text-sm text-amber-400 font-semibold py-2 active:opacity-70" style="display:none">
                    Załaduj więcej
                </button>
            </div>

        </div>
    </div>


</div>
@endsection

@push('scripts')
<script>
    (function () {
        const carousel = document.getElementById('photoCarousel');
        const counter = document.getElementById('photoCounter');
        if (!carousel || !counter) { return; }

        const dots = Array.from(document.querySelectorAll('.carousel-dot'));
        const total = carousel.children.length;

        carousel.addEventListener('scroll', () => {
            const index = Math.round(carousel.scrollLeft / carousel.clientWidth);
            counter.textContent = `${index + 1} / ${total}`;
            dots.forEach((dot, i) => {
                dot.style.background = i === index ? '#f59e0b' : 'rgba(255,255,255,0.4)';
            });
        }, { passive: true });
    })();

    (function () {
        const btn = document.getElementById('likeBtn');
        if (!btn) { return; }

        btn.addEventListener('click', function () {
            const findingId = btn.dataset.id;
            const icon = document.getElementById('likeIcon');
            const count = document.getElementById('likeCount');

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

                const showBtn = document.getElementById('showLikersBtn');
                if (data.likes_count > 0 && !showBtn) {
                    const newBtn = document.createElement('button');
                    newBtn.id = 'showLikersBtn';
                    newBtn.className = 'text-sm text-gray-400 underline underline-offset-2 active:text-gray-300 px-3 py-2 -mx-1 rounded-lg';
                    newBtn.textContent = 'kto polubił?';
                    newBtn.addEventListener('click', openLikersSheet);
                    btn.parentElement.appendChild(newBtn);
                } else if (data.likes_count === 0 && showBtn) {
                    showBtn.remove();
                }

                if (data.is_liked) {
                    count.classList.remove('text-gray-400');
                    count.classList.add('text-red-400');
                    btn.style.background = 'rgba(239,68,68,0.15)';
                } else {
                    count.classList.remove('text-red-400');
                    count.classList.add('text-gray-400');
                    btn.style.background = '#323248';
                }
            })
            .catch(() => {});
        });
    })();

    function openLikersSheet() {
        const overlay = document.getElementById('likersOverlay');
        const sheet = document.getElementById('likersSheet');
        const list = document.getElementById('likersList');
        const findingId = document.getElementById('likeBtn').dataset.id;

        overlay.classList.remove('hidden');
        requestAnimationFrame(() => {
            sheet.classList.remove('translate-y-full');
            sheet.classList.add('translate-y-0');
        });

        list.innerHTML = '<div class="text-center text-gray-500 text-sm py-4">Ładowanie...</div>';

        fetch('/api/findings/' + findingId + '/likes', {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(users => {
            if (!users.length) {
                list.innerHTML = '<div class="text-center text-gray-500 text-sm py-4">Brak polubień</div>';
                return;
            }
            list.innerHTML = users.map(u => {
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
            list.innerHTML = '<div class="text-center text-red-400 text-sm py-4">Błąd ładowania</div>';
        });
    }

    function closeLikersSheet() {
        const overlay = document.getElementById('likersOverlay');
        const sheet = document.getElementById('likersSheet');
        sheet.classList.remove('translate-y-0');
        sheet.classList.add('translate-y-full');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }

    (function () {
        const showBtn = document.getElementById('showLikersBtn');
        if (showBtn) { showBtn.addEventListener('click', openLikersSheet); }

        const closeBtn = document.getElementById('closeLikersBtn');
        if (closeBtn) { closeBtn.addEventListener('click', closeLikersSheet); }

        const overlay = document.getElementById('likersOverlay');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) { closeLikersSheet(); }
            });
        }
    })();

    // --- Komentarze ---
    const FINDING_ID = {{ $finding['id'] }};
    let commentsPage = 1;
    let commentsLastPage = 1;
    let commentPhotosFiles = [];

    function loadComments(page) {
        fetch('/api/findings/' + FINDING_ID + '/comments?page=' + page, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            const list = document.getElementById('commentsList');
            const loading = document.getElementById('commentsLoading');
            const loadMore = document.getElementById('loadMoreComments');

            if (loading) { loading.style.display = 'none'; }

            const comments = res.data || [];
            commentsLastPage = res.meta?.last_page || 1;

            comments.forEach(c => {
                list.insertAdjacentHTML('beforeend', renderComment(c));
            });

            if (commentsPage < commentsLastPage) {
                loadMore.style.display = 'block';
            } else {
                loadMore.style.display = 'none';
            }

            if (comments.length === 0 && page === 1) {
                list.innerHTML = '<div class="text-center text-gray-600 text-sm py-2">Brak komentarzy</div>';
            }
        })
        .catch(() => {
            const loading = document.getElementById('commentsLoading');
            if (loading) { loading.textContent = 'Błąd ładowania komentarzy'; }
        });
    }

    function renderComment(c) {
        const user = c.user || {};
        const avatar = user.avatar_url
            ? '<img src="' + user.avatar_url + '" alt="" style="width:100%;height:100%;object-fit:cover">'
            : '<span class="text-xs font-bold">' + ((user.name || '?').charAt(0).toUpperCase()) + '</span>';

        let photosHtml = '';
        if (c.photos && c.photos.length) {
            photosHtml = '<div class="flex gap-2 mt-2 flex-wrap">' +
                c.photos.map(p =>
                    '<img src="' + p.url + '" alt="" onclick="openLightbox(this.src)" ' +
                    'style="width:80px;height:80px;object-fit:cover;border-radius:0.5rem;cursor:pointer">'
                ).join('') +
                '</div>';
        }

        const deleteBtn = c.is_mine || {{ !empty($finding['is_mine']) ? 'true' : 'false' }}
            ? '<button onclick="deleteComment(' + c.id + ', this)" class="text-xs text-gray-600 hover:text-red-400 ml-auto flex-shrink-0">usuń</button>'
            : '';

        return '<div class="bg-surface-card rounded-xl p-3 mb-2" id="comment-' + c.id + '">'
            + '<div class="flex items-start gap-2">'
            + '<a href="/users/' + user.id + '" class="w-7 h-7 rounded-full bg-[#323248] flex items-center justify-center overflow-hidden flex-shrink-0 text-gray-300">' + avatar + '</a>'
            + '<div class="flex-1 min-w-0">'
            + '<div class="flex items-center gap-2">'
            + '<a href="/users/' + user.id + '" class="text-sm font-semibold text-amber-400">' + (user.name || 'Anonim') + '</a>'
            + '<span class="text-xs text-gray-600">' + c.created_at_human + '</span>'
            + deleteBtn
            + '</div>'
            + '<p class="text-sm text-gray-300 mt-1 whitespace-pre-line break-words [overflow-wrap:anywhere]">' + escapeHtml(c.body) + '</p>'
            + photosHtml
            + '</div>'
            + '</div>'
            + '</div>';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function previewCommentPhotos(input) {
        const preview = document.getElementById('commentPhotosPreview');
        const countEl = document.getElementById('commentPhotoCount');
        const files = Array.from(input.files).slice(0, 4);
        commentPhotosFiles = files;

        if (!files.length) {
            preview.style.display = 'none';
            countEl.textContent = '';
            updateCommentBtn();
            return;
        }

        preview.style.display = 'flex';
        preview.innerHTML = '';
        countEl.textContent = files.length + '/4';

        files.forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'position:relative;width:60px;height:60px;flex-shrink:0';
                wrapper.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:0.5rem">'
                    + '<button onclick="removeCommentPhoto(' + i + ')" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;border-radius:50%;background:#ef4444;color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer">&times;</button>';
                preview.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });

        updateCommentBtn();
    }

    function removeCommentPhoto(index) {
        commentPhotosFiles.splice(index, 1);
        refreshCommentPhotosPreview();
        updateCommentBtn();
    }

    function refreshCommentPhotosPreview() {
        const preview = document.getElementById('commentPhotosPreview');
        const countEl = document.getElementById('commentPhotoCount');
        const input = document.getElementById('commentPhotosInput');

        if (!commentPhotosFiles.length) {
            preview.style.display = 'none';
            countEl.textContent = '';
            input.value = '';
            return;
        }

        preview.style.display = 'flex';
        preview.innerHTML = '';
        countEl.textContent = commentPhotosFiles.length + '/4';

        commentPhotosFiles.forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'position:relative;width:60px;height:60px;flex-shrink:0';
                wrapper.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:0.5rem">'
                    + '<button onclick="removeCommentPhoto(' + i + ')" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;border-radius:50%;background:#ef4444;color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer">&times;</button>';
                preview.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });
    }

    function updateCommentBtn() {
        const body = document.getElementById('commentBody').value.trim();
        document.getElementById('commentSubmitBtn').disabled = !body;
    }

    document.getElementById('commentBody').addEventListener('input', updateCommentBtn);

    function submitComment() {
        const body = document.getElementById('commentBody').value.trim();
        if (!body) { return; }

        const btn = document.getElementById('commentSubmitBtn');
        const errorEl = document.getElementById('commentError');
        btn.disabled = true;
        btn.textContent = 'Wysyłanie...';
        errorEl.style.display = 'none';

        const formData = new FormData();
        formData.append('body', body);
        commentPhotosFiles.forEach((file, i) => {
            formData.append('photos[' + i + ']', file);
        });

        fetch('/api/findings/' + FINDING_ID + '/comments', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => {
            if (!r.ok) { return r.json().then(d => Promise.reject(d)); }
            return r.json();
        })
        .then(res => {
            const comment = res.data || res;
            const list = document.getElementById('commentsList');
            const empty = list.querySelector('.text-gray-600');
            if (empty) { empty.remove(); }

            list.insertAdjacentHTML('afterbegin', renderComment(comment));

            document.getElementById('commentBody').value = '';
            commentPhotosFiles = [];
            document.getElementById('commentPhotosInput').value = '';
            document.getElementById('commentPhotosPreview').style.display = 'none';
            document.getElementById('commentPhotoCount').textContent = '';
            btn.textContent = 'Wyślij';
            updateCommentBtn();
        })
        .catch(err => {
            btn.textContent = 'Wyślij';
            updateCommentBtn();
            const msg = err.errors?.body?.[0] || err.message || 'Błąd dodawania komentarza.';
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
        });
    }

    function deleteComment(commentId, btnEl) {
        if (!confirm('Usunąć komentarz?')) { return; }

        fetch('/api/findings/' + FINDING_ID + '/comments/' + commentId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => {
            if (!r.ok) { return r.json().then(d => Promise.reject(d)); }
            const el = document.getElementById('comment-' + commentId);
            if (el) { el.remove(); }
        })
        .catch(() => {
            alert('Nie udało się usunąć komentarza.');
        });
    }

    document.getElementById('loadMoreComments').addEventListener('click', function () {
        commentsPage++;
        loadComments(commentsPage);
    });

    loadComments(1);

    if (window.location.hash === '#comments') {
        const textarea = document.getElementById('commentBody');
        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => textarea.focus(), 400);
    }
</script>
@endpush
