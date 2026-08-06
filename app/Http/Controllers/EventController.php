<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Imprezy (zloty, giełdy, wydarzenia) — cienki proxy do saperapp-api.
 * Zgłoszenia użytkowników czekają na akceptację administratora.
 */
class EventController extends Controller
{
    public const VOIVODESHIPS = [
        'dolnośląskie',
        'kujawsko-pomorskie',
        'lubelskie',
        'lubuskie',
        'łódzkie',
        'małopolskie',
        'mazowieckie',
        'opolskie',
        'podkarpackie',
        'podlaskie',
        'pomorskie',
        'śląskie',
        'świętokrzyskie',
        'warmińsko-mazurskie',
        'wielkopolskie',
        'zachodniopomorskie',
    ];

    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    private function base(): string
    {
        return config('services.api.url');
    }

    // ----- Views -----

    public function index()
    {
        return view('events.index', [
            'voivodeships' => self::VOIVODESHIPS,
        ]);
    }

    public function create()
    {
        return view('events.create', [
            'voivodeships' => self::VOIVODESHIPS,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/events/{$id}");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        return view('events.show', [
            'event' => $response->json('data') ?? $response->json(),
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/events/{$id}");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        $event = $response->json('data') ?? $response->json();

        // Edytować może właściciel lub administrator; API pilnuje tego też przy zapisie.
        if (! ($event['is_owner'] ?? false) && ! $request->session()->get('api_user.is_admin', false)) {
            abort(403);
        }

        return view('events.edit', [
            'event' => $event,
            'voivodeships' => self::VOIVODESHIPS,
        ]);
    }

    // ----- API passthrough -----

    public function apiIndex(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/events', $request->only('scope', 'voivodeship', 'from', 'to', 'page'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function pendingCount(Request $request)
    {
        // Endpoint moderacyjny — dla zwykłych użytkowników od razu 0 bez pytania API.
        if (! $request->session()->get('api_user.is_admin', false)) {
            return response()->json(['count' => 0]);
        }

        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/events/pending-count');

        return response()->json($response->failed() ? ['count' => 0] : $response->json());
    }

    public function store(Request $request)
    {
        // Formularz wysyła zawsze przez fetch — walidujemy ręcznie, żeby
        // niezależnie od nagłówków webview odpowiedź była JSON-em, a nie
        // przekierowaniem (patrz ExpeditionController::store).
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'photo' => ['required', 'image', 'max:10240'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'voivodeship' => ['required', 'string', Rule::in(self::VOIVODESHIPS)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [], [
            'name' => 'nazwa',
            'description' => 'opis',
            'photo' => 'zdjęcie',
            'starts_at' => 'data rozpoczęcia',
            'ends_at' => 'data zakończenia',
            'voivodeship' => 'województwo',
            'latitude' => 'lokalizacja',
            'longitude' => 'lokalizacja',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $photo = $request->file('photo');

        try {
            $response = Http::withToken($this->apiToken($request))
                ->timeout(30)
                ->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName())
                ->post($this->base().'/events', $request->only(
                    'name', 'description', 'starts_at', 'ends_at', 'voivodeship', 'latitude', 'longitude'
                ));
        } catch (ConnectionException $e) {
            return response()->json([
                'message' => 'Serwer nie odpowiada. Spróbuj ponownie za chwilę.',
            ], 504);
        }

        if ($response->failed()) {
            $errors = $response->json('errors');

            if ($errors) {
                return response()->json(['errors' => $errors], 422);
            }

            return response()->json([
                'message' => 'Nie udało się zapisać imprezy ('.$response->status().').',
            ], 422);
        }

        $request->session()->flash('success', 'Impreza została zgłoszona i czeka na akceptację administratora.');

        return response()->json(['redirect' => route('events.show', $response->json('id'))]);
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:10240'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'voivodeship' => ['required', 'string', Rule::in(self::VOIVODESHIPS)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [], [
            'name' => 'nazwa',
            'description' => 'opis',
            'photo' => 'zdjęcie',
            'starts_at' => 'data rozpoczęcia',
            'ends_at' => 'data zakończenia',
            'voivodeship' => 'województwo',
            'latitude' => 'lokalizacja',
            'longitude' => 'lokalizacja',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // PHP nie czyta multipart body przy PUT — używamy POST z _method=PUT (method spoofing).
        $payload = [
            '_method' => 'PUT',
            ...$request->only('name', 'description', 'starts_at', 'ends_at', 'voivodeship', 'latitude', 'longitude'),
        ];

        $pending = Http::withToken($this->apiToken($request))->timeout(30);

        if ($photo = $request->file('photo')) {
            $pending = $pending->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName());
        }

        try {
            $response = $pending->post($this->base()."/events/{$id}", $payload);
        } catch (ConnectionException $e) {
            return response()->json([
                'message' => 'Serwer nie odpowiada. Spróbuj ponownie za chwilę.',
            ], 504);
        }

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            $errors = $response->json('errors');

            if ($errors) {
                return response()->json(['errors' => $errors], 422);
            }

            return response()->json([
                'message' => 'Nie udało się zapisać zmian ('.$response->status().').',
            ], 422);
        }

        if ($response->json('data.status') === 'pending') {
            $request->session()->flash('success', 'Zmiany zapisane — impreza ponownie czeka na akceptację administratora.');
        } else {
            $request->session()->flash('success', 'Zmiany zostały zapisane.');
        }

        return response()->json(['redirect' => route('events.show', $id)]);
    }

    public function approve(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/events/{$id}/approve");

        return $this->passthrough($response);
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/events/{$id}/reject", ['reason' => $request->input('reason')]);

        return $this->passthrough($response);
    }

    public function destroy(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete($this->base()."/events/{$id}");

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd usuwania.'], 502);
        }

        return response()->json(['redirect' => route('events.index')]);
    }

    private function passthrough($response, int $successStatus = 200)
    {
        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json($response->json() ?? ['message' => 'Błąd.'], $response->status() ?: 502);
        }

        return response()->json($response->json(), $successStatus);
    }
}
