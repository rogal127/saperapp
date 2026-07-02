<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpeditionController extends Controller
{
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
        return view('expeditions.index');
    }

    public function create()
    {
        return view('expeditions.create');
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        return view('expeditions.show', [
            'expedition' => $response->json('data') ?? $response->json(),
        ]);
    }

    // ----- API passthrough -----

    public function pendingCount(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/expeditions/pending-count');

        if ($response->failed()) {
            return response()->json(['count' => 0]);
        }

        return response()->json($response->json());
    }

    public function apiIndex(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/expeditions', $request->only('scope', 'voivodeship', 'from', 'to', 'page'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'area' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'visibility' => ['required', 'in:private,public'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $area = json_decode($request->input('area'), true);

        $response = Http::withToken($this->apiToken($request))
            ->post($this->base().'/expeditions', [
                'name' => $request->name,
                'description' => $request->description,
                'area' => $area,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'visibility' => $request->visibility,
                'publish' => $request->boolean('publish'),
            ]);

        if ($response->failed()) {
            $errors = $response->json('errors') ?? ['name' => ['Błąd zapisu.']];

            if ($request->expectsJson()) {
                return response()->json(['errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        $id = $response->json('data.id');

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('expeditions.show', $id)]);
        }

        return redirect()->route('expeditions.show', $id)
            ->with('success', 'Poszukiwanie utworzone!');
    }

    /**
     * Import polygons from a public Google My Maps link.
     *
     * Fetches the map's KML server-side (browsers cannot due to CORS) and
     * returns every polygon as a GeoJSON-ready structure so the user can pick
     * one to use as the expedition area.
     */
    public function importGoogleMyMaps(Request $request)
    {
        $validated = $request->validate([
            'link' => ['required', 'string', 'max:2048'],
        ]);

        if (! preg_match('/mid=([A-Za-z0-9_-]+)/', $validated['link'], $matches)) {
            return response()->json([
                'message' => 'Nie rozpoznano identyfikatora mapy (mid) w podanym linku.',
            ], 422);
        }

        $mid = $matches[1];

        try {
            $response = Http::timeout(15)->get('https://www.google.com/maps/d/kml', [
                'mid' => $mid,
                'forcekml' => 1,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Nie udało się pobrać mapy z Google.'], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'Nie udało się pobrać mapy. Upewnij się, że jest udostępniona publicznie.',
            ], 502);
        }

        $polygons = $this->parseKmlPolygons($response->body());

        if (empty($polygons)) {
            return response()->json([
                'message' => 'W tej mapie nie znaleziono żadnych wielokątów (obszarów).',
            ], 422);
        }

        return response()->json(['polygons' => $polygons]);
    }

    /**
     * Extract all polygons from a KML document.
     *
     * @return list<array{name: string, area: array{type: string, coordinates: array<int, list<array{0: float, 1: float}>>}}>
     */
    private function parseKmlPolygons(string $kml): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $loaded = $doc->loadXML($kml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $polygons = [];

        foreach ($doc->getElementsByTagName('Placemark') as $placemark) {
            $baseName = '';
            foreach ($placemark->childNodes as $child) {
                if ($child->nodeName === 'name') {
                    $baseName = trim($child->textContent);
                    break;
                }
            }

            $polygonNodes = $placemark->getElementsByTagName('Polygon');
            $polygonCount = $polygonNodes->length;

            foreach ($polygonNodes as $index => $polygonNode) {
                $ring = $this->parseOuterRing($polygonNode);

                if (count($ring) < 4) {
                    continue;
                }

                $name = $baseName !== '' ? $baseName : 'Obszar';
                if ($polygonCount > 1) {
                    $name .= ' ('.($index + 1).')';
                }

                $polygons[] = [
                    'name' => $name,
                    'area' => ['type' => 'Polygon', 'coordinates' => [$ring]],
                ];
            }
        }

        return $polygons;
    }

    /**
     * Parse a polygon's outer boundary into a closed GeoJSON ring of [lng, lat] pairs.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function parseOuterRing(\DOMElement $polygon): array
    {
        $coordsNode = null;

        $outer = $polygon->getElementsByTagName('outerBoundaryIs');
        if ($outer->length > 0) {
            $coordsList = $outer->item(0)->getElementsByTagName('coordinates');
            if ($coordsList->length > 0) {
                $coordsNode = $coordsList->item(0);
            }
        }

        if ($coordsNode === null) {
            $coordsList = $polygon->getElementsByTagName('coordinates');
            if ($coordsList->length > 0) {
                $coordsNode = $coordsList->item(0);
            }
        }

        if ($coordsNode === null) {
            return [];
        }

        $ring = [];
        foreach (preg_split('/\s+/', trim($coordsNode->textContent)) ?: [] as $tuple) {
            if ($tuple === '') {
                continue;
            }

            $parts = explode(',', $tuple);
            if (count($parts) < 2) {
                continue;
            }

            $ring[] = [(float) $parts[0], (float) $parts[1]];
        }

        if (count($ring) < 3) {
            return [];
        }

        $first = $ring[0];
        $last = $ring[count($ring) - 1];
        if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
            $ring[] = $first;
        }

        return $ring;
    }

    public function update(Request $request, int $id)
    {
        $payload = $request->only('name', 'description', 'starts_at', 'ends_at', 'visibility', 'status');

        if ($request->filled('area')) {
            $payload['area'] = json_decode($request->input('area'), true);
        }

        $response = Http::withToken($this->apiToken($request))
            ->put($this->base()."/expeditions/{$id}", $payload);

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json($response->json() ?? ['message' => 'Błąd zapisu.'], 422);
        }

        return response()->json($response->json());
    }

    public function destroy(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete($this->base()."/expeditions/{$id}");

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd usuwania.'], 502);
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('expeditions.index')]);
        }

        return redirect()->route('expeditions.index')->with('success', 'Poszukiwanie usunięte.');
    }

    public function findings(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}/findings", $request->only('page'));

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['data' => []], 502);
        }

        return response()->json($response->json());
    }

    public function invite(Request $request, int $id)
    {
        $request->validate(['user_id' => ['required', 'integer']]);

        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/expeditions/{$id}/participants", [
                'user_id' => $request->user_id,
            ]);

        return $this->passthrough($response, 201);
    }

    public function requestJoin(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/expeditions/{$id}/join");

        return $this->passthrough($response, 201);
    }

    public function joinByCode(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $response = Http::withToken($this->apiToken($request))
            ->post($this->base().'/expeditions/join', ['code' => $request->code]);

        if ($response->status() === 404) {
            return response()->json(['message' => 'Nieprawidłowy kod poszukiwania.'], 404);
        }

        if ($response->failed()) {
            return response()->json($response->json() ?? ['message' => 'Błąd.'], $response->status());
        }

        $expeditionId = $response->json('expedition_id');

        return response()->json([
            'redirect' => route('expeditions.show', $expeditionId),
        ], 201);
    }

    public function acceptParticipant(Request $request, int $id, int $participant)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/expeditions/{$id}/participants/{$participant}/accept");

        return $this->passthrough($response);
    }

    public function declineParticipant(Request $request, int $id, int $participant)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/expeditions/{$id}/participants/{$participant}/decline");

        return $this->passthrough($response);
    }

    public function removeParticipant(Request $request, int $id, int $participant)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete($this->base()."/expeditions/{$id}/participants/{$participant}");

        return $this->passthrough($response);
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
