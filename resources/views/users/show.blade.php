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
    .like-btn { background: none; border: none; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 0.5rem; flex-shrink: 0; min-width: 48px; min-height: 44px; justify-content: center; }
    .like-btn .like-icon { font-size: 1.1rem; }
    .like-btn .like-count { font-size: 0.85rem; color: #9ca3af; }

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
    .fmodal-msg-btn {
        margin-top: 0.875rem; width: 100%; padding: 0.8rem;
        background: transparent; border: 1px solid #f59e0b; color: #f59e0b;
        border-radius: 0.75rem; cursor: pointer; font-size: 0.85rem; font-weight: 700;
    }
    .fmodal-send-btn {
        margin-top: 0.75rem; width: 100%; padding: 0.8rem;
        background: #f59e0b; color: #1a1a2e; font-weight: 700;
        border: none; border-radius: 0.75rem; cursor: pointer; font-size: 0.9rem;
    }
    .fmodal-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .fmodal-textarea {
        width: 100%; background: #2a2a3e; border: 1px solid #404060;
        border-radius: 0.75rem; color: #fff; padding: 0.75rem;
        font-size: 0.82rem; resize: none; outline: none;
        box-sizing: border-box; font-family: inherit; margin-top: 0.75rem;
    }
    .fmodal-textarea:focus { border-color: #f59e0b; }
    #fmodal-status { text-align: center; margin-top: 0.5rem; font-size: 0.78rem; min-height: 1.1em; }
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
                <div class="text-xs text-gray-500 mt-0.5">{{ $profile['role_label'] ?? 'Poszukiwacz' }}</div>
            </div>
            <div class="flex flex-col items-end gap-2">
                <div class="stat-badge">
                    <div class="stat-value">{{ $profile['findings_count'] ?? 0 }}</div>
                    <div class="stat-label">znalezisk</div>
                </div>
                @if(($profile['id'] ?? null) !== $currentUserId)
                <form method="POST" action="{{ route('messages.start-with', $profile['id']) }}">
                    @csrf
                    <button type="submit" style="background:#f59e0b;color:#1a1a2e;font-weight:700;font-size:0.75rem;padding:0.4rem 0.875rem;border-radius:0.75rem;border:none;cursor:pointer;white-space:nowrap">
                        💬 Napisz
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Znaleziska --}}
        @if(empty($regions))
            <div class="text-center py-12 text-gray-500">
                <div class="text-3xl mb-2">🔍</div>
                <div class="text-sm">Brak znalezisk</div>
            </div>
        @else
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Znaleziska według rejonu</div>
            <div id="accordion">
                @foreach($regions as $voivodeship => $counties)
                @php
                    $voiCount = array_sum(array_map('array_sum', $counties));
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
                            $couCount = array_sum($cities);
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
                                @foreach($cities as $city => $count)
                                <div class="acc-city" data-voi="{{ $voivodeship }}" data-cou="{{ $county }}" data-cit="{{ $city }}">
                                    <div class="acc-city-header" onclick="toggleCityAcc(this)">
                                        <span class="acc-city-name">📍 {{ $city }}</span>
                                        <div style="display:flex;align-items:center;gap:0.4rem;">
                                            <span class="acc-city-count">{{ $count }}</span>
                                            <span class="acc-city-arrow">›</span>
                                        </div>
                                    </div>
                                    <div class="acc-city-body"></div>
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
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <button class="like-btn" id="fmodal-like-btn" style="padding:0.4rem">
                            <span class="like-icon" id="fmodal-like-icon">🤍</span>
                            <span class="like-count" id="fmodal-like-count">0</span>
                        </button>
                        <button class="fmodal-close" onclick="closeFindingModal()">✕</button>
                    </div>
                </div>
                <div class="fmodal-desc" id="fmodal-desc"></div>
                <div id="fmodal-gallery" class="fmodal-gallery" style="display:none"></div>
                @if(($profile['id'] ?? null) !== $currentUserId)
                <div id="fmodal-msg-section">
                    <button class="fmodal-msg-btn" id="fmodal-msg-toggle" onclick="toggleMsgForm()">💬 Napisz wiadomość</button>
                    <div id="fmodal-msg-form" style="display:none">
                        <textarea id="fmodal-textarea" class="fmodal-textarea" rows="3" placeholder="Napisz wiadomość do znalazcy…"></textarea>
                        <button class="fmodal-send-btn" id="fmodal-send" onclick="sendFindingMessage()">Wyślij wiadomość</button>
                        <div id="fmodal-status"></div>
                    </div>
                </div>
                @else
                <div id="fmodal-owner-actions" style="display:flex;gap:0.5rem;margin-top:0.875rem">
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
                @endif
            </div>
        </div>
    </div>


</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN    = '{{ csrf_token() }}';
const MESSAGE_BASE  = "{{ url('/api/findings') }}";
const IS_OWN_PROFILE = {{ ($profile['id'] ?? null) === $currentUserId ? 'true' : 'false' }};
const FINDINGS_URL = '{{ route('users.findings', $profile['id'] ?? 0) }}';

function toggleLike(findingId, btn) {
    fetch(`/api/findings/${findingId}/like`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        const icon = btn.querySelector('.like-icon');
        const count = btn.querySelector('.like-count');
        icon.textContent = data.is_liked ? '❤️' : '🤍';
        count.textContent = data.likes_count;

        // Sync the accordion card's like button for the same finding
        const card = document.querySelector(`.finding-card[data-id="${findingId}"]`);
        if (card) {
            const cardBtn = card.querySelector('.like-btn');
            if (cardBtn && cardBtn !== btn) {
                cardBtn.querySelector('.like-icon').textContent = data.is_liked ? '❤️' : '🤍';
                cardBtn.querySelector('.like-count').textContent = data.likes_count;
            }
            card.dataset.liked = data.is_liked ? '1' : '0';
            card.dataset.likes = data.likes_count;
        }
        // Sync the modal like button
        const modalIcon = document.getElementById('fmodal-like-icon');
        const modalCount = document.getElementById('fmodal-like-count');
        if (modalIcon && activeFindingId == findingId) {
            modalIcon.textContent = data.is_liked ? '❤️' : '🤍';
            modalCount.textContent = data.likes_count;
        }
    })
    .catch(() => {});
}

function toggleAcc(header) {
    const body = header.nextElementSibling;
    const arrow = header.querySelector('.acc-arrow, .acc-cou-arrow, .acc-city-arrow');
    const isOpen = body.classList.toggle('open');
    if (arrow) arrow.classList.toggle('open', isOpen);
}

function toggleCityAcc(header) {
    const cityEl = header.closest('.acc-city');
    const body = header.nextElementSibling;
    const arrow = header.querySelector('.acc-city-arrow');
    const isOpen = body.classList.toggle('open');
    arrow.classList.toggle('open', isOpen);

    if (isOpen && !cityEl.dataset.loaded) {
        loadCityFindings(cityEl, body);
    }
}

function loadCityFindings(cityEl, body) {
    const params = new URLSearchParams({
        voivodeship: cityEl.dataset.voi,
        county: cityEl.dataset.cou,
        city: cityEl.dataset.cit,
    });

    body.innerHTML = '<div class="finding-card-meta" style="padding:0.75rem 1rem">Ładowanie…</div>';

    fetch(`${FINDINGS_URL}?${params}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            cityEl.dataset.loaded = '1';
            body.innerHTML = '';
            (data.findings || []).forEach(finding => body.appendChild(buildFindingCard(finding)));
        })
        .catch(() => {
            body.innerHTML = '<div class="finding-card-meta" style="padding:0.75rem 1rem">Nie udało się wczytać znalezisk.</div>';
        });
}

function buildFindingCard(finding) {
    const card = document.createElement('div');
    card.className = 'finding-card';
    card.dataset.type = finding.type || '';
    card.dataset.id = finding.id;
    card.dataset.name = finding.name || '';
    card.dataset.depth = finding.depth_cm || 0;
    card.dataset.date = finding.found_at || '';
    card.dataset.desc = finding.description || '';
    card.dataset.photo = finding.photo_url || '';
    card.dataset.photos = JSON.stringify(finding.photos || []);
    card.dataset.likes = finding.likes_count || 0;
    card.dataset.liked = finding.is_liked ? '1' : '0';
    card.addEventListener('click', () => openFindingModal(card));

    const coverPhoto = (finding.photos || [])[0];
    const coverSrc = coverPhoto
        ? (coverPhoto.is_private ? `/findings/${finding.id}/photos/${coverPhoto.id}` : (coverPhoto.url || ''))
        : (finding.photo_url || '');

    const thumbHtml = coverSrc
        ? `<img src="${escapeHtml(coverSrc)}" alt="" class="finding-thumb">`
        : '<div class="finding-thumb-placeholder">🪙</div>';

    const descHtml = finding.description
        ? `<div class="finding-card-meta" style="margin-top:3px">${escapeHtml(truncate(finding.description, 60))}</div>`
        : '';

    card.innerHTML = `
        ${thumbHtml}
        <div class="flex-1 min-w-0">
            <div class="finding-card-name">${escapeHtml(finding.name || '')}</div>
            <div class="finding-card-meta">📅 ${escapeHtml(finding.found_at || '')}</div>
            <div class="finding-card-depth">📏 ${finding.depth_cm || 0} cm</div>
            ${descHtml}
        </div>
        <button class="like-btn" onclick="event.stopPropagation(); toggleLike(${finding.id}, this)">
            <span class="like-icon">${finding.is_liked ? '❤️' : '🤍'}</span>
            <span class="like-count">${finding.likes_count || 0}</span>
        </button>
    `;

    return card;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function truncate(str, len) {
    return str.length > len ? str.slice(0, len) + '...' : str;
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
        galleryEl.classList.toggle('fmodal-gallery', photos.length > 1);
        galleryEl.style.display = photos.length > 1 ? 'flex' : 'block';
    } else {
        galleryEl.style.display = 'none';
    }

    const msgForm = document.getElementById('fmodal-msg-form');
    if (msgForm) {
        msgForm.style.display = 'none';
        document.getElementById('fmodal-textarea').value = '';
        document.getElementById('fmodal-status').textContent = '';
        const sendBtn = document.getElementById('fmodal-send');
        if (sendBtn) { sendBtn.disabled = false; }
    }

    // Like button in modal
    const modalLikeIcon = document.getElementById('fmodal-like-icon');
    const modalLikeCount = document.getElementById('fmodal-like-count');
    const modalLikeBtn = document.getElementById('fmodal-like-btn');
    modalLikeIcon.textContent = card.dataset.liked === '1' ? '❤️' : '🤍';
    modalLikeCount.textContent = card.dataset.likes || '0';
    modalLikeBtn.onclick = function () { toggleLike(activeFindingId, modalLikeBtn); };

    if (IS_OWN_PROFILE && activeFindingId) {
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

function toggleMsgForm() {
    const form = document.getElementById('fmodal-msg-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function confirmDelete() {
    return confirm('Na pewno chcesz usunąć to znalezisko?');
}

function sendFindingMessage() {
    const body = document.getElementById('fmodal-textarea').value.trim();
    if (!body || !activeFindingId) { return; }

    const btn    = document.getElementById('fmodal-send');
    const status = document.getElementById('fmodal-status');
    btn.disabled       = true;
    status.textContent = 'Wysyłanie…';
    status.style.color = '#9ca3af';

    fetch(`${MESSAGE_BASE}/${activeFindingId}/message`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ body }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok) {
            status.textContent = '✓ Wiadomość wysłana!';
            status.style.color = '#34d399';
            document.getElementById('fmodal-textarea').value = '';
            setTimeout(closeFindingModal, 1500);
        } else {
            status.textContent = data.message ?? 'Nie udało się wysłać.';
            status.style.color = '#f87171';
            btn.disabled = false;
        }
    })
    .catch(() => {
        status.textContent = 'Błąd połączenia.';
        status.style.color = '#f87171';
        btn.disabled = false;
    });
}
</script>
@endpush
