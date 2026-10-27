@extends('layouts.app')
@section('title', 'Usunięcie konta')
@section('hideNav', true)

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom overflow-y-auto">
    <div class="px-6 py-8 max-w-2xl w-full mx-auto">

        <div class="mb-8 text-center">
            <div class="text-6xl mb-4">🗑️</div>
            <h1 class="text-3xl font-bold text-white">Usunięcie konta w aplikacji Historius</h1>
            <p class="text-gray-400 mt-2">
                Aplikacja Historius (historius.pl) · administrator: Prospekcja Artur Troncik
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">Jak poprosić o usunięcie konta</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>
                    Wyślij wiadomość e-mail na adres
                    <a href="mailto:poczta.undergroundpassion@gmail.com?subject=Usuni%C4%99cie%20konta%20Historius" class="welcome-modal-link">poczta.undergroundpassion@gmail.com</a>
                    z tematem <strong class="text-white">„Usunięcie konta Historius”</strong>.
                </li>
                <li>
                    Wyślij ją z adresu e-mail przypisanego do konta i podaj w treści swoją nazwę użytkownika –
                    pozwoli nam to potwierdzić, że prośba pochodzi od właściciela konta.
                </li>
                <li>
                    Usuniemy konto bez zbędnej zwłoki, nie później niż w ciągu miesiąca od otrzymania prośby,
                    i potwierdzimy to wiadomością zwrotną.
                </li>
            </ol>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">Dane, które zostaną usunięte</h2>
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>dane konta: adres e-mail, nazwa użytkownika, hasło (skrót), awatar, data założenia konta;</li>
                <li>wszystkie znaleziska – publiczne i prywatne – wraz ze zdjęciami, opisami, lokalizacjami i raportami PDF;</li>
                <li>komentarze dodane przez użytkownika oraz komentarze innych osób pod jego znaleziskami;</li>
                <li>polubienia i listy ulubionych;</li>
                <li>wiadomości prywatne (całe rozmowy, w których użytkownik brał udział) oraz wpisy na czacie globalnym, w tym zdjęcia i nagrania głosowe;</li>
                <li>utworzone poszukiwania (rajdy) i informacje o udziale w poszukiwaniach;</li>
                <li>dodane imprezy oraz zapisane zgody WKZ;</li>
                <li>sesje logowania i tokeny dostępu.</li>
            </ul>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">Dane zachowywane i okresy przechowywania</h2>
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>kopie zapasowe – do 1 miesiąca od usunięcia konta, po czym dane są trwale usuwane;</li>
                <li>logi systemowe (adres IP, data i godzina, wykonane operacje) – do 1 miesiąca;</li>
                <li>dokumentacja reklamacji – 1 rok od zakończenia postępowania reklamacyjnego;</li>
                <li>dane niezbędne do ustalenia, dochodzenia lub obrony roszczeń albo wymagane przepisami prawa – przez okres wynikający z tych przepisów.</li>
            </ul>
        </div>

        <p class="text-sm text-gray-400 text-center leading-relaxed">
            Szczegóły znajdziesz w <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="welcome-modal-link">Polityce prywatności</a>
            oraz <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="welcome-modal-link">Regulaminie</a>.
        </p>

    </div>
</div>
@endsection
