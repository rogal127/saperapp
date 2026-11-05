@extends('layouts.app')
@section('title', 'Polityka prywatności')
@section('hideNav', true)

@section('content')
<div class="flex flex-col h-full safe-top safe-bottom overflow-y-auto">
    <div class="px-6 py-8 max-w-2xl w-full mx-auto">

        <div class="mb-8 text-center">
            <div class="text-6xl mb-4">🔒</div>
            <h1 class="text-3xl font-bold text-white">Polityka prywatności serwisu historius.pl</h1>
            <p class="text-gray-400 mt-2">
                Wersja 1.0, obowiązuje od dnia 11.08.2026
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">1. Administrator danych</h2>
            <div class="space-y-2 text-sm text-gray-300 leading-relaxed">
                <p>
                    Administratorem danych osobowych Użytkowników serwisu historius.pl jest
                    Prospekcja Artur Troncik, NIP: 6431633368, adres poczty elektronicznej:
                    <a href="mailto:poczta.undergroundpassion@gmail.com" class="welcome-modal-link">poczta.undergroundpassion@gmail.com</a>.
                </p>
                <p>
                    We wszystkich sprawach dotyczących danych osobowych można kontaktować się
                    pod wskazanym adresem poczty elektronicznej lub pisemnie na adres
                    Administratora. Administrator nie wyznaczył inspektora ochrony danych.
                </p>
                <p>Pojęcia pisane wielką literą mają znaczenie nadane im w <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="welcome-modal-link">Regulaminie</a> serwisu.</p>
            </div>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">2. Jakie dane przetwarzamy</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>
                    <strong class="text-white">Dane Konta:</strong> adres poczty elektronicznej, Nazwa użytkownika, hasło
                    (przechowywane wyłącznie w postaci skrótu kryptograficznego, uniemożliwiającej jego odczytanie),
                    awatar – jeżeli Użytkownik go doda, data założenia Konta.
                </li>
                <li>
                    <strong class="text-white">Treści zamieszczane przez Użytkownika:</strong> tytuły, opisy i zdjęcia Znalezisk,
                    oznaczenia miejsca znalezienia, komentarze, reakcje, listy ulubionych, dane Poszukiwań
                    (teren oznaczony na mapie, termin, opis) oraz informacje o zapisaniu się na Poszukiwanie.
                </li>
                <li>
                    <strong class="text-white">Treść korespondencji:</strong> wiadomości prywatne wymieniane z innymi
                    Użytkownikami oraz wpisy na czacie globalnym.
                </li>
                <li>
                    <strong class="text-white">Dane eksploatacyjne:</strong> adres IP, identyfikator sesji, data i godzina
                    korzystania z Serwisu, rodzaj przeglądarki i systemu operacyjnego, informacje o wykonanych
                    operacjach – zapisywane w logach systemowych.
                </li>
                <li>
                    <strong class="text-white">Dane zgłoszeń:</strong> treść reklamacji, zgłoszeń nadużyć i sprzeciwów wraz z
                    danymi kontaktowymi osoby zgłaszającej.
                </li>
            </ol>
            <p class="text-sm text-gray-300 leading-relaxed mt-3">
                Administrator nie zbiera danych szczególnych kategorii, o których mowa w art. 9 RODO,
                i prosi o niezamieszczanie takich danych w treściach publikowanych w Serwisie.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">3. Cele, podstawy prawne i okresy przechowywania</h2>
            <div class="space-y-4 text-sm text-gray-300 leading-relaxed">
                <div>
                    <p class="font-semibold text-white">Założenie i prowadzenie Konta, udostępnianie funkcji Serwisu (mapa, Znaleziska, komentarze, ulubione, Poszukiwania, eksport PDF)</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. b RODO – wykonanie umowy</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> przez czas posiadania Konta</p>
                </div>
                <div>
                    <p class="font-semibold text-white">Umożliwienie korespondencji prywatnej i korzystania z czatu globalnego</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. b RODO</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> wiadomości prywatne – natychmiast od wysłania, nie dłużej niż do usunięcia Konta jednej ze stron; wpisy na czacie – natychmiast od wysłania, nie dłużej niż do usunięcia Konta jednej ze stron</p>
                </div>
                <div>
                    <p class="font-semibold text-white">Zapewnienie bezpieczeństwa Serwisu, zapobieganie nadużyciom, moderacja treści i rozpatrywanie zgłoszeń</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. f RODO – prawnie uzasadniony interes polegający na ochronie Serwisu i jego Użytkowników</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> logi systemowe – 1 miesiąc; dokumentacja zgłoszeń – 1 miesiąc</p>
                </div>
                <div>
                    <p class="font-semibold text-white">Rozpatrywanie reklamacji</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. b oraz lit. c RODO</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> 1 rok od zakończenia postępowania reklamacyjnego</p>
                </div>
                <div>
                    <p class="font-semibold text-white">Wykonanie obowiązków wynikających z przepisów prawa, w tym udzielanie informacji organom państwa (art. 18 ust. 6 ustawy o świadczeniu usług drogą elektroniczną)</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. c RODO</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> przez okres wynikający z przepisów</p>
                </div>
                <div>
                    <p class="font-semibold text-white">Ustalenie, dochodzenie lub obrona roszczeń</p>
                    <p><span class="text-gray-400">Podstawa prawna:</span> art. 6 ust. 1 lit. f RODO</p>
                    <p><span class="text-gray-400">Okres przechowywania:</span> do upływu terminu przedawnienia roszczeń</p>
                </div>
                <p>
                    Podanie adresu poczty elektronicznej, Nazwy użytkownika i hasła jest dobrowolne,
                    lecz niezbędne do założenia Konta. Podanie pozostałych danych, w tym awatara i
                    treści Znalezisk, jest w pełni dobrowolne.
                </p>
            </div>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">4. Lokalizacja Znalezisk</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>Dokładne współrzędne miejsca znalezienia są przechowywane w postaci zaszyfrowanej.</li>
                <li>
                    Innym Użytkownikom Znalezisko publiczne prezentowane jest wyłącznie przez wskazanie
                    najbliższej miejscowości. Przypisanie do miejscowości nie stanowi anonimizacji – dane
                    pozostają powiązane z Kontem Użytkownika.
                </li>
                <li>
                    Z przesyłanych plików graficznych usuwane są metadane, w tym informacje o położeniu
                    geograficznym i urządzeniu, którym wykonano zdjęcie.
                </li>
                <li>
                    Serwis korzysta z map dostarczanych przez www.openstreetmap.org. Serwis nie przekazuje
                    dostawcy map dokładnych współrzędnych Znalezisk.
                </li>
            </ol>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">5. Odbiorcy danych</h2>
            <p class="text-sm text-gray-300 leading-relaxed mb-2">Dane mogą być udostępniane lub powierzane do przetwarzania:</p>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>
                    innym Użytkownikom – w zakresie Nazwy użytkownika, awatara, treści publicznych oraz danych,
                    które Użytkownik sam zdecydował się ujawnić, a także adresatom wiadomości prywatnych;
                </li>
                <li>dostawcy infrastruktury serwerowej i usług hostingowych: OVH, Francja;</li>
                <li>dostawcy usługi poczty transakcyjnej (wiadomości aktywacyjne, powiadomienia): WP, Polska;</li>
                <li>dostawcy map: OpenStreetMap, Wielka Brytania;</li>
                <li>dostawcy systemu kopii zapasowych oraz narzędzi monitorujących działanie Serwisu: OVH, Francja;</li>
                <li>podmiotom świadczącym Administratorowi obsługę prawną i księgową;</li>
                <li>organom państwa – w zakresie i na zasadach wynikających z przepisów prawa.</li>
            </ol>
            <div class="space-y-2 text-sm text-gray-300 leading-relaxed mt-3">
                <p>
                    Z podmiotami przetwarzającymi dane w imieniu Administratora zawarto umowy powierzenia
                    przetwarzania danych osobowych spełniające wymogi art. 28 RODO.
                </p>
                <p>Dane nie są przekazywane poza Europejski Obszar Gospodarczy.</p>
            </div>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">6. Prawa Użytkownika</h2>
            <p class="text-sm text-gray-300 leading-relaxed mb-2">Użytkownikowi przysługuje prawo do:</p>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>dostępu do swoich danych oraz otrzymania ich kopii (art. 15 RODO);</li>
                <li>sprostowania danych (art. 16 RODO);</li>
                <li>usunięcia danych (art. 17 RODO);</li>
                <li>ograniczenia przetwarzania (art. 18 RODO);</li>
                <li>przenoszenia danych przetwarzanych na podstawie umowy lub zgody (art. 20 RODO);</li>
                <li>
                    wniesienia sprzeciwu wobec przetwarzania opartego na prawnie uzasadnionym interesie,
                    z przyczyn związanych ze szczególną sytuacją Użytkownika (art. 21 RODO);
                </li>
                <li>
                    cofnięcia zgody w dowolnym momencie, bez wpływu na zgodność z prawem przetwarzania
                    dokonanego przed jej cofnięciem;
                </li>
                <li>
                    wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych, ul. Stawki 2, 00-193 Warszawa.
                </li>
            </ol>
            <p class="text-sm text-gray-300 leading-relaxed mt-3">
                Żądania można kierować na adres
                <a href="mailto:poczta.undergroundpassion@gmail.com" class="welcome-modal-link">poczta.undergroundpassion@gmail.com</a>.
                Administrator odpowiada bez zbędnej zwłoki, nie później niż w terminie miesiąca od otrzymania żądania.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">7. Usunięcie Konta</h2>
            <p class="text-sm text-gray-300 leading-relaxed">
                Z chwilą usunięcia Konta usuwane są dane Konta, Znaleziska prywatne, wiadomości prywatne oraz
                informacje o zapisach na Poszukiwania. Znaleziska publiczne i komentarze są usuwane wraz z Kontem.
                Dane mogą być przechowywane w kopiach zapasowych przez okres miesiąca, po czym są trwale usuwane.
                Administrator może zachować dane niezbędne do ustalenia, dochodzenia lub obrony roszczeń oraz do
                wykonania obowiązków wynikających z przepisów prawa.
                Instrukcja usunięcia Konta: <a href="{{ route('legal.account-deletion') }}" class="welcome-modal-link">historius.pl/usuwanie-konta</a>.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">8. Bezpieczeństwo danych</h2>
            <p class="text-sm text-gray-300 leading-relaxed">
                Administrator stosuje środki techniczne i organizacyjne odpowiadające ryzyku, o których mowa
                w art. 32 RODO, w szczególności: szyfrowanie połączeń protokołem TLS, przechowywanie haseł
                wyłącznie w postaci skrótów kryptograficznych, szyfrowanie dokładnych współrzędnych Znalezisk,
                ograniczenie dostępu do danych do osób upoważnionych, rejestrowanie operacji w logach
                systemowych, wykonywanie kopii zapasowych oraz bieżącą aktualizację oprogramowania. W razie
                naruszenia ochrony danych osobowych Administrator wykonuje obowiązki wynikające z art. 33 i 34 RODO.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">9. Pliki cookies i podobne technologie</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed">
                <li>
                    Serwis wykorzystuje pliki cookies oraz zbliżone technologie zapisywane w urządzeniu końcowym
                    Użytkownika, na zasadach określonych w art. 399 ustawy z dnia 12 lipca 2024 r. – Prawo
                    komunikacji elektronicznej.
                </li>
                <li>
                    Pliki niezbędne do świadczenia usługi żądanej przez Użytkownika, w tym pliki sesyjne
                    i uwierzytelniające oraz zapamiętujące wybory dotyczące zgód, wykorzystywane są bez
                    konieczności uzyskania zgody.
                </li>
                <li>
                    Pozostałe pliki, w szczególności analityczne, wykorzystywane są wyłącznie po wyrażeniu zgody.
                    Zgoda może zostać w każdej chwili wycofana za pomocą panelu ustawień dostępnego pod adresem
                    <a href="{{ route('cookies.settings') }}" class="welcome-modal-link">historius.pl/ustawienia-cookies</a>.
                    Odmowa wyrażenia zgody jest możliwa w tym samym kroku i w ten sam sposób co jej wyrażenie.
                </li>
                <li>Wykaz stosowanych plików wraz z celem i okresem ich przechowywania:</li>
            </ol>
            <div class="overflow-x-auto mt-3">
                <table class="w-full text-xs text-gray-300 text-left">
                    <thead class="text-gray-400">
                        <tr>
                            <th class="py-2 pr-3 font-semibold">Nazwa</th>
                            <th class="py-2 pr-3 font-semibold">Rodzaj</th>
                            <th class="py-2 pr-3 font-semibold">Cel</th>
                            <th class="py-2 font-semibold">Okres</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr><td class="py-2 pr-3 font-mono">laravel-session</td><td class="py-2 pr-3">niezbędny</td><td class="py-2 pr-3">utrzymanie sesji zalogowanego Użytkownika</td><td class="py-2">10 godzin</td></tr>
                        <tr><td class="py-2 pr-3 font-mono">XSRF-TOKEN</td><td class="py-2 pr-3">niezbędny</td><td class="py-2 pr-3">zabezpieczenie przed atakami CSRF</td><td class="py-2">10 godzin</td></tr>
                        <tr><td class="py-2 pr-3 font-mono">api_remember</td><td class="py-2 pr-3">niezbędny</td><td class="py-2 pr-3">utrzymanie zalogowania po wygaśnięciu sesji (opcja „zapamiętaj mnie”)</td><td class="py-2">30 dni</td></tr>
                        <tr><td class="py-2 pr-3 font-mono">analytics_consent</td><td class="py-2 pr-3">niezbędny</td><td class="py-2 pr-3">zapamiętanie decyzji dotyczących cookies</td><td class="py-2">12 miesięcy</td></tr>
                        <tr><td class="py-2 pr-3 font-mono">_ga</td><td class="py-2 pr-3">analityczny</td><td class="py-2 pr-3">Google Analytics — rozróżnianie użytkowników</td><td class="py-2">24 miesiące</td></tr>
                        <tr><td class="py-2 pr-3 font-mono">_ga_[identyfikator]</td><td class="py-2 pr-3">analityczny</td><td class="py-2 pr-3">Google Analytics — utrzymanie stanu sesji pomiarowej</td><td class="py-2">24 miesiące</td></tr>
                    </tbody>
                </table>
            </div>
            <ol start="5" class="list-decimal list-inside space-y-2 text-sm text-gray-300 leading-relaxed mt-3">
                <li>
                    Użytkownik może samodzielnie określić warunki przechowywania plików cookies za pomocą ustawień
                    przeglądarki. Ograniczenie stosowania plików niezbędnych może uniemożliwić korzystanie z Konta.
                </li>
            </ol>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">10. Zautomatyzowane podejmowanie decyzji</h2>
            <p class="text-sm text-gray-300 leading-relaxed">
                Dane Użytkowników nie podlegają zautomatyzowanemu podejmowaniu decyzji wywołującemu skutki prawne
                ani w podobny sposób istotnie wpływającemu na Użytkownika, w tym profilowaniu.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">11. Małoletni</h2>
            <p class="text-sm text-gray-300 leading-relaxed">
                Serwis przeznaczony jest dla osób pełnoletnich. Administrator nie kieruje usług do dzieci i nie
                zbiera świadomie ich danych. W razie stwierdzenia, że Konto założyła osoba niepełnoletnia, dane
                zostaną usunięte.
            </p>
        </div>

        <div class="card mb-6">
            <h2 class="text-lg font-bold text-white mb-3">12. Zmiany Polityki prywatności</h2>
            <p class="text-sm text-gray-300 leading-relaxed">
                O zmianach Polityki prywatności Administrator informuje pocztą elektroniczną oraz komunikatem
                w Serwisie z co najmniej 14-dniowym wyprzedzeniem. Aktualna wersja dokumentu wraz z datą jej
                wejścia w życie jest zawsze dostępna pod adresem historius.pl/polityka.
            </p>
        </div>

        <p class="text-sm text-gray-400 text-center leading-relaxed">
            <a href="{{ asset('polityka-prywatnosci.pdf') }}" target="_blank" rel="noopener" class="welcome-modal-link">Pobierz wersję PDF</a>
            · <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="welcome-modal-link">Regulamin</a>
        </p>

    </div>
</div>
@endsection
