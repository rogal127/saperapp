<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindingController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    public function create(Request $request)
    {
        return view('findings.create', [
            'initialPinId' => $request->query('pin_id'),
        ]);
    }

    public function wkzConsents(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/wkz-consents');

        if ($response->failed()) {
            return response()->json([]);
        }

        return response()->json($response->json());
    }

    public function findingCategories(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/finding-categories');

        if ($response->failed()) {
            return response()->json([]);
        }

        return response()->json($response->json());
    }

    public function store(Request $request)
    {
        $hasPinId = $request->filled('pin_id');

        $request->validate([
            'pin_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => [$hasPinId ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$hasPinId ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:255'],
            'city_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'city_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'voivodeship' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'wkz_consent_id' => ['nullable', 'integer'],
            'finding_category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'in:archaeological_monument,monument,non_monument'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:10240'],
        ]);

        $pending = Http::withToken($this->apiToken($request));

        foreach ($request->file('photos', []) as $index => $photo) {
            $pending = $pending->attach(
                "photos[{$index}]",
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            );
        }

        $payload = [
            'name' => $request->name,
            'description' => $request->description,
            'depth_cm' => $request->depth_cm,
            'wkz_consent_id' => $request->wkz_consent_id ?: null,
            'finding_category_id' => $request->finding_category_id ?: null,
            'type' => $request->type ?: null,
        ];

        if ($hasPinId) {
            $payload['pin_id'] = $request->pin_id;
        } else {
            $payload['latitude'] = $request->latitude;
            $payload['longitude'] = $request->longitude;
            $payload['city'] = $request->city;
            $payload['city_lat'] = $request->city_lat;
            $payload['city_lng'] = $request->city_lng;
            $payload['voivodeship'] = $request->voivodeship;
            $payload['county'] = $request->county;
        }

        $response = $pending->post(config('services.api.url').'/findings', $payload);

        if ($response->failed()) {
            return back()->withErrors($response->json('errors') ?? ['name' => 'Błąd zapisu.'])->withInput();
        }

        $createdPinId = $response->json('pin_id');
        $city = $response->json('city');
        $voivodeship = $response->json('voivodeship');
        $location = $city ? $city.($voivodeship ? ', '.$voivodeship : '') : null;

        return redirect()->route('findings.created')->with([
            'success' => 'Znalezisko poprawnie dodane!',
            'created_pin_id' => $createdPinId,
            'created_location' => $location,
        ]);
    }

    public function created(Request $request)
    {
        if (! $request->session()->has('created_pin_id')) {
            return redirect()->route('home');
        }

        return view('findings.created', [
            'pinId' => $request->session()->get('created_pin_id'),
            'location' => $request->session()->get('created_location'),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings/{$id}");

        if ($response->status() === 404) {
            abort(404);
        }

        if ($response->failed()) {
            abort(502);
        }

        return view('findings.show', ['finding' => $response->json('data') ?? $response->json()]);
    }

    public function map()
    {
        return view('findings.map');
    }

    public function sendMessage(Request $request, int $findingId)
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->post(config('services.api.url')."/findings/{$findingId}/message", [
                'body' => $request->body,
            ]);

        if ($response->status() === 422) {
            return response()->json($response->json(), 422);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd wysyłania wiadomości.'], 502);
        }

        return response()->json($response->json(), 201);
    }

    public function mapSearch(Request $request)
    {
        $request->validate([
            'zoom' => ['required', 'integer', 'min:0', 'max:19'],
            'sw_lat' => ['required', 'numeric'],
            'sw_lng' => ['required', 'numeric'],
            'ne_lat' => ['required', 'numeric'],
            'ne_lng' => ['required', 'numeric'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/map/clusters', $request->only('zoom', 'sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function pins(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/pins');

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function pinFindings(Request $request, int $pinId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/pins/{$pinId}/findings");

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], $response->status());
        }

        return response()->json($response->json());
    }

    public function edit(Request $request, int $id)
    {
        $token = $this->apiToken($request);
        $base = config('services.api.url');

        $findingResponse = Http::withToken($token)->get("{$base}/findings/{$id}");

        if ($findingResponse->status() === 403 || $findingResponse->status() === 404) {
            abort($findingResponse->status());
        }
        if ($findingResponse->failed()) {
            abort(502);
        }

        $consentsResponse = Http::withToken($token)->get("{$base}/wkz-consents");
        $wkzConsents = $consentsResponse->successful() ? $consentsResponse->json() : [];

        $categoriesResponse = Http::withToken($token)->get("{$base}/finding-categories");
        $findingCategories = $categoriesResponse->successful() ? $categoriesResponse->json() : [];

        $finding = $findingResponse->json('data') ?? $findingResponse->json();

        return view('findings.edit', [
            'finding' => $finding,
            'wkzConsents' => $wkzConsents,
            'findingCategories' => $findingCategories,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'private_notes' => ['nullable', 'string', 'max:2000'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'wkz_consent_id' => ['nullable', 'integer'],
            'finding_category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'in:archaeological_monument,monument,non_monument'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:10240'],
            'delete_photo_ids' => ['nullable', 'array'],
            'delete_photo_ids.*' => ['integer'],
        ]);

        $pending = Http::withToken($this->apiToken($request));

        foreach ($request->file('photos', []) as $index => $photo) {
            $pending = $pending->attach(
                "photos[{$index}]",
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            );
        }

        // PHP nie czyta multipart body przy PUT — używamy POST z _method=PUT (method spoofing)
        $payload = [
            '_method' => 'PUT',
            'name' => $request->name,
            'description' => $request->description,
            'private_notes' => $request->private_notes ?? '',
            'depth_cm' => $request->depth_cm,
            'wkz_consent_id' => $request->wkz_consent_id ?: '',
            'finding_category_id' => $request->finding_category_id ?: '',
            'type' => $request->type ?: '',
        ];

        // Bracket notation, aby PHP po stronie API sparsował to jako tablicę także w multipart.
        foreach (array_values($request->input('delete_photo_ids', [])) as $index => $photoId) {
            $payload["delete_photo_ids[{$index}]"] = $photoId;
        }

        $response = $pending->post(config('services.api.url')."/findings/{$id}", $payload);

        if ($response->status() === 403) {
            abort(403);
        }

        if ($response->failed()) {
            return back()->withErrors($response->json('errors') ?? ['name' => 'Błąd zapisu.'])->withInput();
        }

        return redirect()->route('findings.map')->with('success', 'Znalezisko zaktualizowane!');
    }

    public function destroy(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete(config('services.api.url')."/findings/{$id}");

        if (request()->expectsJson()) {
            if ($response->status() === 403) {
                return response()->json(['message' => 'Brak uprawnień.'], 403);
            }
            if ($response->failed()) {
                return response()->json(['message' => 'Błąd usuwania.'], 502);
            }

            return response()->json(['message' => 'Usunięto.']);
        }

        return redirect()->route('findings.map')->with('success', 'Znalezisko usunięte.');
    }
}
