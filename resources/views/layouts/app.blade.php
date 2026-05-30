<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SaperApp')</title>
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
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
