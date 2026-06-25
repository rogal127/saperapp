<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .screen { height: 100dvh; display: flex; flex-direction: column; background: #1e1e2e; color: #e2e8f0; }
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
            gap: 2px; padding: 0.5rem 1rem; flex: 1;
            font-size: 0.65rem; color: #6b7280;
            touch-action: manipulation;
            transition: color 0.15s;
        }
        .nav-item.active { color: #f59e0b; }
        .nav-icon { font-size: 1.5rem; line-height: 1; }
        select.input-field option { background: #323248; }
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
            <a href="{{ route('messages.index') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" id="nav-messages">
                <span class="nav-icon">💬</span><span>Wiadomości</span>
            </a>
        </div>
        @endunless
    </div>
    <!-- Lightbox -->
    <div id="lightbox" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.93);align-items:center;justify-content:center;" onclick="closeLightbox()">
        <img id="lightbox-img" style="max-width:100%;max-height:100%;object-fit:contain;padding:8px;" alt="">
        <button style="position:absolute;top:calc(env(safe-area-inset-top,0px) + 12px);right:16px;background:rgba(255,255,255,0.18);border:none;color:#fff;border-radius:50%;width:40px;height:40px;font-size:1.3rem;cursor:pointer;display:flex;align-items:center;justify-content:center;" onclick="closeLightbox()">✕</button>
    </div>
    @stack('scripts')
    <script>
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').style.display = 'flex';
    }
    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.getElementById('lightbox-img').src = '';
    }
    </script>
    <script>
    (function () {
        fetch("{{ route('messages.unread') }}")
            .then(r => r.json())
            .then(data => {
                if (data.count > 0) {
                    const link = document.getElementById('nav-messages');
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
