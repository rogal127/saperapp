@extends('layouts.app')
@section('title', ($event['name'] ?? 'Impreza').' – Historius')

@push('styles')
<style>
    #event-map { height: 14rem; border-radius: 1rem; overflow: hidden; }
    .leaflet-container { background: #1a1a2e; }
</style>
@endpush

@php
    $monthsGen = ['stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    $formatDay = function (?string $date) use ($monthsGen): array {
        $parts = explode('-', (string) $date);
        return count($parts) === 3
            ? ['d' => (int) $parts[2], 'm' => $monthsGen[(int) $parts[1] - 1] ?? '', 'y' => (int) $parts[0]]
            : ['d' => null, 'm' => '', 'y' => null];
    };
    $a = $formatDay($event['starts_at'] ?? null);
    $b = $formatDay($event['ends_at'] ?? null);
    if ($a['d'] === $b['d'] && $a['m'] === $b['m'] && $a['y'] === $b['y']) {
        $dateRange = "{$a['d']} {$a['m']} {$a['y']}";
    } elseif ($a['m'] === $b['m'] && $a['y'] === $b['y']) {
        $dateRange = "{$a['d']}–{$b['d']} {$a['m']} {$a['y']}";
    } elseif ($a['y'] === $b['y']) {
        $dateRange = "{$a['d']} {$a['m']} – {$b['d']} {$b['m']} {$a['y']}";
    } else {
        $dateRange = "{$a['d']} {$a['m']} {$a['y']} – {$b['d']} {$b['m']} {$b['y']}";
    }

    $isAdmin = (bool) session('api_user.is_admin');
    $isOwner = (bool) ($event['is_owner'] ?? false);
    $status = $event['status'] ?? 'approved';
@endphp

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('events.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <h1 class="text-lg font-bold text-white flex-1 truncate">{{ $event['name'] ?? 'Impreza' }}</h1>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-4">

        @if(session('success'))
        <div class="px-4 py-3 bg-green-900/50 border border-green-700 rounded-xl text-green-300 text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- Status banner (widoczny dla zgłaszającego i administratora) --}}
        @if($status === 'pending')
        <div class="px-4 py-3 bg-amber-900/40 border border-amber-700 rounded-xl text-amber-300 text-sm">
            ⏳ Impreza czeka na akceptację administratora — nie jest jeszcze widoczna na liście.
        </div>
        @elseif($status === 'rejected')
        <div class="px-4 py-3 bg-red-900/40 border border-red-700 rounded-xl text-red-300 text-sm">
            ⛔ Impreza została odrzucona.
            @if(!empty($event['rejection_reason']))
            <span class="block mt-1 text-red-200/80">Powód: {{ $event['rejection_reason'] }}</span>
            @endif
        </div>
        @endif

        {{-- Photo --}}
        @if(!empty($event['photo_url']))
        <img src="{{ $event['photo_url'] }}" alt="{{ $event['name'] ?? '' }}"
             class="w-full aspect-video object-cover rounded-2xl cursor-pointer"
             onclick="openLightbox(this.src)">
        @endif

        {{-- Details --}}
        <div class="card flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-xl flex-shrink-0">📅</div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white">{{ $dateRange }}</p>
                    @if(($event['phase'] ?? '') === 'active')
                    <p class="text-xs text-green-400 font-semibold">● Impreza właśnie trwa</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center text-xl flex-shrink-0">🗺️</div>
                <p class="text-sm text-gray-200">woj. {{ $event['voivodeship'] ?? '—' }}</p>
            </div>
            @if(!empty($event['organizer']))
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-xl flex-shrink-0 overflow-hidden">
                    @if(!empty($event['organizer']['avatar_url']))
                    <img src="{{ $event['organizer']['avatar_url'] }}" alt="" class="w-full h-full object-cover">
                    @else
                    👤
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">Zgłasza</p>
                    <a href="{{ route('users.show', $event['organizer']['id']) }}" class="text-sm font-semibold text-white truncate">{{ $event['organizer']['name'] }}</a>
                </div>
            </div>
            @endif
        </div>

        {{-- Description --}}
        <div class="card">
            <h2 class="text-sm font-bold text-gray-300 mb-2">📝 Opis</h2>
            <p class="text-sm text-gray-200 whitespace-pre-line">{{ $event['description'] ?? '' }}</p>
        </div>

        {{-- Map --}}
        @if(isset($event['latitude'], $event['longitude']))
        <div>
            <h2 class="text-sm font-bold text-gray-300 mb-2 ml-1">📍 Miejsce imprezy</h2>
            <div id="event-map"></div>
            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $event['latitude'] }},{{ $event['longitude'] }}"
               target="_blank" rel="noopener"
               class="btn-secondary block text-center mt-2">🧭 Prowadź do miejsca imprezy</a>
        </div>
        @endif

        {{-- Moderation (admin) --}}
        @if($isAdmin && $status !== 'approved')
        <div class="flex gap-2">
            <button type="button" id="rejectBtn" class="btn-secondary">⛔ Odrzuć</button>
            <button type="button" id="approveBtn" class="btn-primary">✅ Zatwierdź</button>
        </div>
        @elseif($isAdmin && $status === 'approved')
        <button type="button" id="rejectBtn" class="btn-secondary">⛔ Cofnij i odrzuć</button>
        @endif

        {{-- Edit / delete (owner or admin) --}}
        @if($isOwner || $isAdmin)
        <a href="{{ route('events.edit', $event['id'] ?? 0) }}" class="btn-secondary block text-center">✏️ Edytuj imprezę</a>
        <button type="button" id="deleteBtn" class="text-red-400 text-sm font-semibold py-2">🗑️ Usuń imprezę</button>
        @endif

        <div class="h-4"></div>
    </div>

</div>

{{-- Reject reason modal --}}
@if($isAdmin)
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
@endif
@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    @if(isset($event['latitude'], $event['longitude']))
    const map = L.map('event-map', {
        center: [{{ $event['latitude'] }}, {{ $event['longitude'] }}],
        zoom: 12,
        zoomControl: false,
        attributionControl: false,
        dragging: false,
        scrollWheelZoom: false,
        touchZoom: false,
        doubleClickZoom: false,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    L.marker([{{ $event['latitude'] }}, {{ $event['longitude'] }}], {
        icon: L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41],
        }),
    }).addTo(map);
    @endif

    const approveBtn = document.getElementById('approveBtn');
    if (approveBtn) {
        approveBtn.addEventListener('click', () => {
            approveBtn.disabled = true;
            fetch("{{ route('events.approve', $event['id'] ?? 0) }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => { if (!r.ok) { throw new Error(); } window.location.reload(); })
            .catch(() => { approveBtn.disabled = false; alert('Nie udało się zatwierdzić imprezy.'); });
        });
    }

    const rejectBtn = document.getElementById('rejectBtn');
    if (rejectBtn) {
        const modal = document.getElementById('rejectModal');
        const reasonInput = document.getElementById('rejectReason');
        const rejectError = document.getElementById('rejectError');

        rejectBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            reasonInput.focus();
        });
        document.getElementById('rejectCancel').addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            rejectError.classList.add('hidden');
        });
        document.getElementById('rejectSubmit').addEventListener('click', () => {
            const reason = reasonInput.value.trim();
            if (!reason) {
                rejectError.textContent = 'Podaj powód odrzucenia.';
                rejectError.classList.remove('hidden');
                return;
            }
            fetch("{{ route('events.reject', $event['id'] ?? 0) }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason }),
            })
            .then(r => { if (!r.ok) { throw new Error(); } window.location.reload(); })
            .catch(() => {
                rejectError.textContent = 'Nie udało się odrzucić imprezy.';
                rejectError.classList.remove('hidden');
            });
        });
    }

    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            if (!confirm('Na pewno usunąć tę imprezę? Tej operacji nie można cofnąć.')) { return; }
            fetch("{{ route('events.destroy', $event['id'] ?? 0) }}", {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.redirect) { window.location.href = data.redirect; return; }
                alert(data.message || 'Nie udało się usunąć imprezy.');
            })
            .catch(() => alert('Błąd połączenia.'));
        });
    }
})();
</script>
@endpush
