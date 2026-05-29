<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FindingFactory extends Factory
{
    private static array $names = [
        'Denar rzymski – Antoninus Pius',
        'Grosz krakowski – Kazimierz Wielki',
        'Trojak koronny – Stefan Batory',
        'Szeląg gdański – Zygmunt III',
        'Talar saski XVIII w.',
        'Guzik mundurowy – Armia Pruska',
        'Guzik mundurowy – Armia Carska',
        'Klamra paska – okres średniowiecza',
        'Brakteat – XIII wiek',
        'Denar krzyżowy – XI wiek',
        'Półgrosz litewski – Zygmunt I',
        'Grosz srebrny – Władysław Łokietek',
        'Medalik Matki Boskiej – XIX w.',
        'Pierścień brązowy – okres nowożytny',
        'Moneta – 5 groszy II RP',
        'Moneta – 10 groszy II RP',
        'Moneta – 1 złoty II RP',
        'Złota moneta – Dukat niderlandzki',
        'Grot bełtu kuszy – XIV wiek',
        'Podkowa konia – XVIII wiek',
        'Stempel lakowy – XVIII w.',
        'Łyżka cynowa – XVII wiek',
        'Zawieszka srebrna – XV wiek',
        'Fibula brązowa – okres wpływów rzymskich',
        'Siekierka brązowa – epoka brązu',
        'Okucie pasa – XII wiek',
        'Pieczęć ołowiana – XVI w.',
        'Moneta – 2 Pfennig pruski',
        'Moneta – Kopiejka carska',
        'Guzik ceramiczny – XIX w.',
    ];

    private static array $descriptions = [
        'Znaleziona na polu po zbiorach pszenicy. Stan zachowania bardzo dobry, czytelny awers i rewers.',
        'Głęboka orka wyrzuciła znalezisko tuż przy starej miedzy. Lekka patyna, brak uszkodzeń.',
        'Okolice dawnego traktu handlowego. Srebro utlenione, ale detale wyraźne.',
        'Teren po dawnym folwarku. Moneta złamana, oba fragmenty odzyskane.',
        'Łąka przy rzece – teren zalewowy. Doskonały stan, jakby wyszła z obiegu niedawno.',
        'Skraj lasu, dawne pobojowisko. Charakterystyczna patyna bojowa.',
        'Pole orne przy ruinach młyna. Mocno skorodowane, wymaga konserwacji.',
        'Ogród przy starym domu. Wymagała delikatnego czyszczenia, po konserwacji piękna.',
        null,
        null,
        'Ciekawy okaz – bardzo rzadki nominał jak na ten region.',
        'Pierwsze znalezisko na tej działce. Dobry omen!',
    ];

    // Clusters around Polish cities and regions
    private static array $clusters = [
        [52.23, 21.01, 0.8],   // Warszawa
        [50.06, 19.94, 0.7],   // Kraków
        [51.11, 17.04, 0.6],   // Wrocław
        [54.35, 18.65, 0.5],   // Gdańsk
        [53.13, 23.16, 0.4],   // Białystok
        [51.76, 19.46, 0.5],   // Łódź
        [50.27, 18.99, 0.4],   // Katowice
        [52.41, 16.93, 0.5],   // Poznań
        [53.78, 20.49, 0.4],   // Olsztyn
        [50.87, 20.63, 0.5],   // Kielce
        [51.25, 22.57, 0.4],   // Lublin
        [53.43, 14.54, 0.4],   // Szczecin
        [49.83, 19.04, 0.4],   // Bielsko-Biała
        [51.92, 15.51, 0.4],   // Zielona Góra
        [50.04, 22.00, 0.4],   // Rzeszów
    ];

    public function definition(): array
    {
        $cluster = $this->faker->randomElement(self::$clusters);
        [$baseLat, $baseLng, $spread] = $cluster;

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(self::$names),
            'description' => $this->faker->randomElement(self::$descriptions),
            'latitude' => round($baseLat + $this->faker->randomFloat(4, -$spread, $spread), 7),
            'longitude' => round($baseLng + $this->faker->randomFloat(4, -$spread, $spread), 7),
            'depth_cm' => $this->faker->numberBetween(3, 45),
            'photo_path' => null,
            'created_at' => $this->faker->dateTimeBetween('-3 years', 'now'),
        ];
    }
}
