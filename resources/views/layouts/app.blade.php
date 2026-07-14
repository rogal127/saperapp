<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <title>@yield('title', 'Historius')</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#f59e0b', dark: '#d97706' },
                        surface: { DEFAULT: '#1e1e2e', card: '#2a2a3e', input: '#323248' },
                        accent: '#7c3aed',
                    },
                    fontFamily: { sans: ['system-ui', 'sans-serif'] },
                },
            },
        }
    </script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; overflow: hidden; }
        .screen { height: 100vh; height: 100dvh; display: flex; flex-direction: column; background: #1e1e2e; color: #e2e8f0; }
        .safe-top { padding-top: env(safe-area-inset-top, 0px); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0px); }
        .leaflet-container { background: #2a2a3e !important; }
        input:focus, textarea:focus, select:focus { outline: none; }
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #1a1a2e;
            font-weight: 700;
            border-radius: 1rem;
            padding: 1rem 2rem;
            font-size: 1rem;
            width: 100%;
            touch-action: manipulation;
            transition: transform 0.1s, opacity 0.1s;
        }
        .btn-primary:active { transform: scale(0.97); opacity: 0.9; }
        .btn-secondary {
            background: #323248;
            color: #e2e8f0;
            font-weight: 600;
            border-radius: 1rem;
            padding: 1rem 2rem;
            font-size: 1rem;
            width: 100%;
            touch-action: manipulation;
            transition: transform 0.1s;
        }
        .btn-secondary:active { transform: scale(0.97); }
        .input-field {
            background: #323248;
            color: #e2e8f0;
            border: 1px solid #404060;
            border-radius: 0.875rem;
            padding: 0.875rem 1rem;
            width: 100%;
            font-size: 1rem;
        }
        .input-field:focus { border-color: #f59e0b; }
        .input-field::placeholder { color: #6b7280; }
        .card { background: #2a2a3e; border-radius: 1.25rem; padding: 1.25rem; }
        .nav-bar {
            background: #13131f;
            border-top: 1px solid #2a2a3e;
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-shrink: 0;
        }
        .nav-item {
            display: flex; flex-direction: column; align-items: center;
            gap: 2px; padding: 0.5rem 0.25rem; flex: 1; min-width: 0;
            font-size: 0.6rem; color: #6b7280;
            white-space: nowrap;
            touch-action: manipulation;
            transition: color 0.15s;
        }
        .nav-item.active { color: #f59e0b; }
        .nav-icon { font-size: 1.5rem; line-height: 1; }
        select.input-field option { background: #323248; }
        .welcome-modal {
            display: none; position: fixed; inset: 0; z-index: 3000;
            background: rgba(0,0,0,0.75); align-items: center; justify-content: center; padding: 1.5rem;
        }
        .welcome-modal.open { display: flex; }
        .welcome-modal-card {
            background: #1a1a2e; border: 1px solid #2a2a3e; border-radius: 1.25rem;
            width: 100%; max-width: 380px; max-height: 100%; overflow-y: auto;
            display: flex; flex-direction: column; animation: welcomeModalIn 0.25s ease;
        }
        @keyframes welcomeModalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .welcome-modal-img {
            width: 100%; max-height: 30vh; object-fit: contain; background: #13131f;
            display: block; flex-shrink: 0;
        }
        .welcome-modal-body { padding: 1.25rem; flex-shrink: 0; }
        .welcome-modal-title { font-weight: 800; font-size: 1.1rem; color: #fff; margin: 0 0 0.5rem; }
        .welcome-modal-text { font-size: 0.9rem; color: #cbd5e0; line-height: 1.5; margin: 0 0 1.25rem; }
        .welcome-modal-link { color: #f59e0b; font-weight: 700; text-decoration: underline; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="screen">
        <div style="flex:1;min-height:0;display:flex;flex-direction:column">
            @yield('content')
        </div>

        @unless($__env->yieldContent('hideNav'))
        <div class="nav-bar safe-bottom">
            <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span><span>Start</span>
            </a>
            <a href="{{ route('findings.map') }}" class="nav-item {{ request()->routeIs('findings.map') ? 'active' : '' }}">
                <span class="nav-icon">🗺️</span><span>Mapa</span>
            </a>
            <a href="{{ route('findings.create') }}" class="nav-item">
                <span class="nav-icon" style="font-weight:900;color:#f59e0b;">+</span><span>Dodaj</span>
            </a>
            <a href="{{ route('expeditions.index') }}" class="nav-item {{ request()->routeIs('expeditions.*') ? 'active' : '' }}" id="nav-expeditions">
                <span class="nav-icon">🧭</span><span>Poszukiwania</span>
            </a>
            <a href="{{ route('messages.index') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" id="nav-messages">
                <span class="nav-icon">💬</span><span>Wiadomości</span>
            </a>
        </div>
        @endunless
    </div>
    @if (session('show_welcome_modal'))
    <!-- Welcome modal -->
    <div id="welcome-modal" class="welcome-modal open" onclick="handleWelcomeModalBackdrop(event)">
        <div class="welcome-modal-card" onclick="event.stopPropagation()">
            <img src="https://pub-8be7d8ab5bcc40f697863889aedf500f.r2.dev/avatars/9HUg1ygBiANnT5K6g8bZK0nPNLuXjENknbw3tie8.jpg" alt="Historius" class="welcome-modal-img">
            <div class="welcome-modal-body">
                <p class="welcome-modal-title">HISTORIUS!</p>
                <p class="welcome-modal-text">
                    Tu dzielimy się informacjami o naszych odkryciach, wspólnie z naukowcami odkrywamy historie i 
dbamy o jej zachowanie. 
                    <a href="https://info.r-dev.pl" target="_blank" rel="noopener" class="welcome-modal-link">Kliknij TUTAJ</a> i poczytaj jak działa Historius.
                </p>
                <button type="button" class="btn-primary" onclick="closeWelcomeModal()">Rozumiem</button>
            </div>
        </div>
    </div>
    @endif
    <!-- Lightbox -->
    <div id="lightbox" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.93);align-items:center;justify-content:center;" onclick="closeLightbox()">
        <img id="lightbox-img" style="max-width:100%;max-height:100%;object-fit:contain;padding:8px;" alt="">
        <button id="lightbox-prev" style="display:none;position:absolute;top:50%;left:12px;transform:translateY(-50%);background:rgba(255,255,255,0.18);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.5rem;cursor:pointer;align-items:center;justify-content:center;" onclick="event.stopPropagation();lightboxNav(-1)">‹</button>
        <button id="lightbox-next" style="display:none;position:absolute;top:50%;right:12px;transform:translateY(-50%);background:rgba(255,255,255,0.18);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.5rem;cursor:pointer;align-items:center;justify-content:center;" onclick="event.stopPropagation();lightboxNav(1)">›</button>
        <button style="position:absolute;top:calc(env(safe-area-inset-top,0px) + 12px);right:16px;background:rgba(255,255,255,0.18);border:none;color:#fff;border-radius:50%;width:40px;height:40px;font-size:1.3rem;cursor:pointer;display:flex;align-items:center;justify-content:center;" onclick="event.stopPropagation();closeLightbox()">✕</button>
    </div>
    @if(session('api_user'))
    <script src="https://unpkg.com/pusher-js@8/dist/web/pusher.min.js"></script>
    <script src="https://unpkg.com/laravel-echo@2/dist/echo.iife.js"></script>
    <script>
        window.Echo = new Echo.default({
            broadcaster: 'reverb',
            key: @json(config('services.reverb.key')),
            wsHost: @json(config('services.reverb.host')),
            wsPort: {{ (int) config('services.reverb.port') }},
            wssPort: {{ (int) config('services.reverb.port') }},
            forceTLS: {{ config('services.reverb.scheme') === 'https' ? 'true' : 'false' }},
            enabledTransports: ['ws', 'wss'],
            authEndpoint: @json(route('broadcasting.auth')),
            auth: { headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } },
        });
    </script>
    @endif
    @stack('scripts')
    <script>
    function closeWelcomeModal() {
        document.getElementById('welcome-modal').classList.remove('open');
    }
    function handleWelcomeModalBackdrop(e) {
        if (e.target === document.getElementById('welcome-modal')) { closeWelcomeModal(); }
    }
    </script>
    <script>
    let lightboxPhotos = [];
    let lightboxIndex = 0;

    function openLightbox(src, photos, index) {
        lightboxPhotos = Array.isArray(photos) ? photos : [];
        lightboxIndex = Number.isInteger(index) ? index : lightboxPhotos.indexOf(src);
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').style.display = 'flex';

        const showNav = lightboxPhotos.length > 1;
        document.getElementById('lightbox-prev').style.display = showNav ? 'flex' : 'none';
        document.getElementById('lightbox-next').style.display = showNav ? 'flex' : 'none';
    }
    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.getElementById('lightbox-img').src = '';
        lightboxPhotos = [];
    }
    function lightboxNav(direction) {
        if (!lightboxPhotos.length) { return; }
        lightboxIndex = (lightboxIndex + direction + lightboxPhotos.length) % lightboxPhotos.length;
        document.getElementById('lightbox-img').src = lightboxPhotos[lightboxIndex];
    }
    document.addEventListener('keydown', function (e) {
        if (document.getElementById('lightbox').style.display !== 'flex') { return; }
        if (e.key === 'ArrowLeft') { lightboxNav(-1); }
        else if (e.key === 'ArrowRight') { lightboxNav(1); }
        else if (e.key === 'Escape') { closeLightbox(); }
    });
    </script>
    @if(session('api_user'))
    <script>
    (function () {
        const MY_ID = {{ (int) (session('api_user.id') ?? session('api_user')['id'] ?? 0) }};
        const ON_CHAT_PAGE = {{ request()->routeIs('chat.show') ? 'true' : 'false' }};
        const OPEN_CONVERSATION_ID = {{ request()->routeIs('messages.show') ? (int) request()->route('id') : 'null' }};
        let unreadCount = 0;

        function renderBadge() {
            const link = document.getElementById('nav-messages');
            if (!link) { return; }
            let badge = document.getElementById('nav-messages-badge');
            if (unreadCount <= 0) {
                if (badge) { badge.remove(); }
                return;
            }
            if (!badge) {
                link.style.position = 'relative';
                badge = document.createElement('span');
                badge.id = 'nav-messages-badge';
                badge.style.cssText = 'position:absolute;top:6px;right:calc(50% - 18px);background:#f59e0b;color:#1a1a2e;border-radius:999px;font-size:0.55rem;font-weight:700;padding:1px 5px;min-width:16px;text-align:center;';
                link.appendChild(badge);
            }
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        }

        Promise.all([
            fetch("{{ route('messages.unread') }}").then(r => r.json()).catch(() => ({count: 0})),
            fetch("{{ route('chat.unread') }}").then(r => r.json()).catch(() => ({count: 0})),
        ]).then(([conversations, chat]) => {
            unreadCount = (conversations.count || 0) + (chat.count || 0);
            renderBadge();
        });

        if (!ON_CHAT_PAGE) {
            window.Echo.channel('chat').listen('.ChatMessageSent', (e) => {
                if (e.user_id !== MY_ID) {
                    unreadCount++;
                    renderBadge();
                }
            });
        }

        if (MY_ID) {
            window.Echo.private('users.' + MY_ID).listen('.ConversationMessageSent', (e) => {
                if (e.conversation_id !== OPEN_CONVERSATION_ID) {
                    unreadCount++;
                    renderBadge();
                }
            });
        }
    })();
    </script>
    @endif
    <script>
    (function () {
        fetch("{{ route('expeditions.pending-count') }}")
            .then(r => r.json())
            .then(data => {
                if (data.count > 0) {
                    const link = document.getElementById('nav-expeditions');
                    if (!link) { return; }
                    link.style.position = 'relative';
                    const badge = document.createElement('span');
                    badge.style.cssText = 'position:absolute;top:6px;right:calc(50% - 18px);background:#f59e0b;color:#1a1a2e;border-radius:999px;font-size:0.55rem;font-weight:700;padding:1px 5px;min-width:16px;text-align:center;';
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    link.appendChild(badge);
                }
            })
            .catch(() => {});
    })();
    </script>
</body>
</html>
