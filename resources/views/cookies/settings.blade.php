@extends('layouts.app')
@section('title', 'Ustawienia cookies')
@section('hideNav', true)

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom overflow-y-auto">

    {{-- Back button --}}
    <div class="px-4 pt-4">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-gray-400 text-sm py-2 px-1">
            ‹ Wróć
        </a>
    </div>

    {{-- Content --}}
    <div class="flex-1 flex flex-col justify-center px-6 pb-8">

        <div class="mb-8 text-center">
            <div class="text-6xl mb-4">🍪</div>
            <h1 class="text-3xl font-bold text-white">Ustawienia cookies</h1>
            <p class="text-gray-400 mt-2">Zarządzaj zgodą na pliki cookies analityczne</p>
        </div>

        <div class="card mb-6">
            <p class="text-sm text-gray-300 leading-relaxed">
                Pliki cookies niezbędne do działania aplikacji (np. sesja logowania) są używane zawsze i nie wymagają zgody.
                Pliki cookies analityczne (Google Analytics) są używane wyłącznie po wyrażeniu zgody.
                Zgodę możesz wyrazić, odmówić jej lub wycofać w każdej chwili poniżej.
                Szczegóły znajdziesz w <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="welcome-modal-link">Polityce prywatności</a>
                oraz <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="welcome-modal-link">Regulaminie</a>.
            </p>
        </div>

        <div class="card mb-6 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Twoja obecna decyzja</p>
            <p id="consent-status" class="text-lg font-bold text-white">…</p>
        </div>

        <div class="flex flex-col gap-3">
            <button type="button" class="btn-primary" onclick="setAnalyticsConsent('granted'); refreshConsentStatus()">
                Wyrażam zgodę
            </button>
            <button type="button" class="btn-secondary" onclick="setAnalyticsConsent('denied'); refreshConsentStatus()">
                Nie wyrażam zgody / wycofuję zgodę
            </button>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function refreshConsentStatus() {
        var consent = window.getAnalyticsConsent();
        var label = consent === 'granted'
            ? 'Zgoda wyrażona ✅'
            : (consent === 'denied' ? 'Brak zgody ❌' : 'Nie podjęto jeszcze decyzji');
        document.getElementById('consent-status').textContent = label;
    }
    document.addEventListener('DOMContentLoaded', refreshConsentStatus);
</script>
@endpush
