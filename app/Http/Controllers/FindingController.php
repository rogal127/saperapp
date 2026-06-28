<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'description' => ['nullable', 'string', 'max:65535'],
            'latitude' => [$hasPinId ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$hasPinId ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:255'],
            'city_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'city_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'voivodeship' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_private' => ['nullable', 'boolean'],
            'wkz_consent_id' => ['nullable', 'integer'],
            'finding_category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'in:archaeological_monument,monument,non_monument'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:10240'],
            'photos_private' => ['nullable', 'array'],
            'photos_private.*' => ['integer', 'min:0'],
            'report' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $pending = Http::withToken($this->apiToken($request));

        foreach ($request->file('photos', []) as $index => $photo) {
            $pending = $pending->attach(
                "photos[{$index}]",
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            );
        }

        if ($request->hasFile('report')) {
            $report = $request->file('report');
            $pending = $pending->attach(
                'report',
                file_get_contents($report->getRealPath()),
                $report->getClientOriginalName()
            );
        }

        $payload = [
            'name' => $request->name,
            'description' => $request->description,
            'depth_cm' => $request->depth_cm,
            'is_private' => $request->boolean('is_private') ? '1' : '0',
            'wkz_consent_id' => $request->wkz_consent_id ?: null,
            'finding_category_id' => $request->finding_category_id ?: null,
            'type' => $request->type ?: null,
        ];

        // Bracket notation, aby API sparsowało to jako tablicę w multipart.
        foreach (array_values($request->input('photos_private', [])) as $index => $photoIndex) {
            $payload["photos_private[{$index}]"] = $photoIndex;
        }

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
            $errors = $response->json('errors') ?? ['name' => ['Błąd zapisu.']];

            if ($request->expectsJson()) {
                return response()->json(['errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        Cache::forget('findings_count');

        $createdPinId = $response->json('pin_id');
        $city = $response->json('city');
        $voivodeship = $response->json('voivodeship');
        $location = $city ? $city.($voivodeship ? ', '.$voivodeship : '') : null;

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Znalezisko poprawnie dodane!');
            $request->session()->flash('created_pin_id', $createdPinId);
            $request->session()->flash('created_location', $location);

            return response()->json(['redirect' => route('findings.created')]);
        }

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

    public function photo(Request $request, int $id, int $photoId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings/{$id}/photos/{$photoId}");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function report(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings/{$id}/report");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sprawozdanie.pdf"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function map()
    {
        $findingsCount = Cache::remember('findings_count', 3600, function () {
            $response = Http::get(config('services.api.url').'/findings/count');

            return $response->successful() ? $response->json('count') : null;
        });

        return view('findings.map', ['findingsCount' => $findingsCount]);
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

    public function updatePin(Request $request, int $pinId)
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:255'],
            'city_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'city_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'voivodeship' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->put(config('services.api.url')."/pins/{$pinId}", $request->only(
                'latitude', 'longitude', 'city', 'city_lat', 'city_lng', 'voivodeship', 'county'
            ));

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd aktualizacji pinezki.'], 502);
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

    public function toggleLike(Request $request, int $finding)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post(config('services.api.url')."/findings/{$finding}/like");

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd.'], 502);
        }

        return response()->json($response->json());
    }

    public function likers(Request $request, int $finding)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings/{$finding}/likes");

        if ($response->failed()) {
            return response()->json([], 502);
        }

        return response()->json($response->json());
    }

    public function browse()
    {
        return view('findings.browse');
    }

    public function browseApi(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/browse', $request->only('voivodeship', 'city', 'county', 'name', 'user_id', 'sort', 'page'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function voivodeships(Request $request)
    {
        $cached = Cache::get('voivodeships');
        if ($cached !== null) {
            return response()->json($cached);
        }

        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/voivodeships');

        if ($response->failed()) {
            return response()->json([]);
        }

        $data = $response->json();
        Cache::put('voivodeships', $data, 86400);

        return response()->json($data);
    }

    public function searchUsers(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/users/search', ['q' => $request->query('q', '')]);

        if ($response->failed()) {
            return response()->json([]);
        }

        return response()->json($response->json());
    }

    public function comments(Request $request, int $findingId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings/{$findingId}/comments", $request->only('page'));

        if ($response->failed()) {
            return response()->json(['data' => []], 502);
        }

        return response()->json($response->json());
    }

    public function storeComment(Request $request, int $findingId)
    {
        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:4'],
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

        $response = $pending->post(config('services.api.url')."/findings/{$findingId}/comments", [
            'body' => $request->body,
        ]);

        if ($response->status() === 422) {
            return response()->json($response->json(), 422);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd dodawania komentarza.'], 502);
        }

        return response()->json($response->json(), 201);
    }

    public function destroyComment(Request $request, int $findingId, int $commentId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete(config('services.api.url')."/findings/{$findingId}/comments/{$commentId}");

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd usuwania komentarza.'], 502);
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
            'redirectTo' => $request->query('from') === 'browse' ? route('findings.browse') : null,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'private_notes' => ['nullable', 'string', 'max:65535'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_private' => ['nullable', 'boolean'],
            'wkz_consent_id' => ['nullable', 'integer'],
            'finding_category_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'in:archaeological_monument,monument,non_monument'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:10240'],
            'photos_private' => ['nullable', 'array'],
            'photos_private.*' => ['integer', 'min:0'],
            'delete_photo_ids' => ['nullable', 'array'],
            'delete_photo_ids.*' => ['integer'],
            'make_private_photo_ids' => ['nullable', 'array'],
            'make_private_photo_ids.*' => ['integer'],
            'make_public_photo_ids' => ['nullable', 'array'],
            'make_public_photo_ids.*' => ['integer'],
            'report' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'delete_report' => ['nullable', 'boolean'],
        ]);

        $pending = Http::withToken($this->apiToken($request));

        foreach ($request->file('photos', []) as $index => $photo) {
            $pending = $pending->attach(
                "photos[{$index}]",
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            );
        }

        if ($request->hasFile('report')) {
            $report = $request->file('report');
            $pending = $pending->attach(
                'report',
                file_get_contents($report->getRealPath()),
                $report->getClientOriginalName()
            );
        }

        // PHP nie czyta multipart body przy PUT — używamy POST z _method=PUT (method spoofing)
        $payload = [
            '_method' => 'PUT',
            'name' => $request->name,
            'description' => $request->description,
            'private_notes' => $request->private_notes ?? '',
            'depth_cm' => $request->depth_cm,
            'is_private' => $request->boolean('is_private') ? '1' : '0',
            'wkz_consent_id' => $request->wkz_consent_id ?: '',
            'finding_category_id' => $request->finding_category_id ?: '',
            'type' => $request->type ?: '',
            'delete_report' => $request->boolean('delete_report') ? '1' : '0',
        ];

        // Bracket notation, aby PHP po stronie API sparsował to jako tablicę także w multipart.
        foreach (array_values($request->input('delete_photo_ids', [])) as $index => $photoId) {
            $payload["delete_photo_ids[{$index}]"] = $photoId;
        }

        foreach (array_values($request->input('photos_private', [])) as $index => $photoIndex) {
            $payload["photos_private[{$index}]"] = $photoIndex;
        }

        foreach (array_values($request->input('make_private_photo_ids', [])) as $index => $photoId) {
            $payload["make_private_photo_ids[{$index}]"] = $photoId;
        }

        foreach (array_values($request->input('make_public_photo_ids', [])) as $index => $photoId) {
            $payload["make_public_photo_ids[{$index}]"] = $photoId;
        }

        $response = $pending->post(config('services.api.url')."/findings/{$id}", $payload);

        if ($response->status() === 403) {
            abort(403);
        }

        if ($response->failed()) {
            $errors = $response->json('errors') ?? ['name' => ['Błąd zapisu.']];

            if ($request->expectsJson()) {
                return response()->json(['errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        $redirectUrl = $request->input('redirect_to', route('findings.map'));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirectUrl]);
        }

        return redirect($redirectUrl)->with('success', 'Znalezisko zaktualizowane!');
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

            Cache::forget('findings_count');

            return response()->json(['message' => 'Usunięto.']);
        }

        if ($response->successful()) {
            Cache::forget('findings_count');
        }

        return redirect()->route('findings.map')->with('success', 'Znalezisko usunięte.');
    }
}
