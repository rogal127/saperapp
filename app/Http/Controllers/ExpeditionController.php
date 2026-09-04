<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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

    public function edit(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}");

        if (in_array($response->status(), [403, 404], true)) {
            abort($response->status());
        }

        if ($response->failed()) {
            abort(502);
        }

        $expedition = $response->json('data') ?? $response->json();

        // Only the leader may change an expedition; the API enforces this too.
        if (! ($expedition['is_leader'] ?? false)) {
            abort(403);
        }

        return view('expeditions.edit', [
            'expedition' => $expedition,
        ]);
    }

    // ----- API passthrough -----

    public function pendingCount(Request $request)
    {
        $token = $this->apiToken($request);

        $data = Cache::remember('expeditions.pending_count.'.md5($token), 300, function () use ($token) {
            $response = Http::withToken($token)
                ->get($this->base().'/expeditions/pending-count');

            return $response->failed() ? ['count' => 0] : $response->json();
        });

        return response()->json($data);
    }

    public function apiIndex(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/expeditions', $request->only('scope', 'voivodeship', 'from', 'to', 'page', 'q'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    /**
     * Single expedition as JSON (including the `area` geometry), used by the
     * "Live" mode of the findings map to draw the selected expedition's terrain.
     */
    public function apiShow(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}");

        if (in_array($response->status(), [403, 404], true)) {
            return response()->json(['error' => 'Nie znaleziono poszukiwania'], $response->status());
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    /**
     * Expeditions overlapping the current map viewport, used by the
     * "Poszukiwania" mode of the findings map.
     */
    public function mapAreas(Request $request)
    {
        $request->validate([
            'zoom' => ['required', 'integer', 'min:0', 'max:19'],
            'sw_lat' => ['required', 'numeric'],
            'sw_lng' => ['required', 'numeric'],
            'ne_lat' => ['required', 'numeric'],
            'ne_lng' => ['required', 'numeric'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/expeditions/map', $request->only('zoom', 'sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function store(Request $request)
    {
        // This endpoint is always called via fetch (see expeditions/create.blade.php),
        // so it always answers with JSON. We validate manually instead of using
        // $request->validate() to guarantee JSON errors regardless of how the
        // webview sends its Accept/X-Requested-With headers (which, if missed,
        // would make Laravel redirect back to the form and "reload" the screen).
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'area' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'visibility' => ['required', 'in:private,public'],
            'findings_private' => ['nullable', 'boolean'],
            'publish' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area = json_decode($request->input('area'), true);

        try {
            $response = Http::withToken($this->apiToken($request))
                ->timeout(15)
                ->post($this->base().'/expeditions', [
                    'name' => $request->name,
                    'description' => $request->description,
                    'area' => $area,
                    'starts_at' => $request->starts_at,
                    'ends_at' => $request->ends_at,
                    'visibility' => $request->visibility,
                    'findings_private' => $request->boolean('findings_private'),
                    'publish' => $request->boolean('publish'),
                ]);
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
                'message' => 'Nie udało się zapisać poszukiwania ('.$response->status().').',
            ], 422);
        }

        $id = $response->json('data.id');

        return response()->json(['redirect' => route('expeditions.show', $id)]);
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

        $mid = $this->resolveMyMapsId($validated['link']);

        if ($mid === null) {
            return response()->json([
                'message' => 'Nie rozpoznano mapy w linku. Użyj linku „Udostępnij" z Google My Maps.',
            ], 422);
        }

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
     * Resolve a Google My Maps id (mid) from a full or shortened link.
     *
     * Shortened links (goo.gl / maps.app.goo.gl) are followed server-side to
     * reveal the final URL that contains the mid. Only known Google hosts are
     * followed to avoid SSRF.
     */
    private function resolveMyMapsId(string $link): ?string
    {
        $link = trim($link);

        if (! preg_match('#^https?://#i', $link)) {
            $link = 'https://'.ltrim($link, '/');
        }

        if (preg_match('/mid=([A-Za-z0-9_-]+)/', $link, $matches)) {
            return $matches[1];
        }

        $host = strtolower((string) parse_url($link, PHP_URL_HOST));
        $allowedHosts = ['goo.gl', 'maps.app.goo.gl', 'www.google.com', 'google.com'];

        if (! in_array($host, $allowedHosts, true)) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($link);
        } catch (\Throwable $e) {
            return null;
        }

        $finalUrl = (string) $response->effectiveUri();
        if (preg_match('/mid=([A-Za-z0-9_-]+)/', $finalUrl, $matches)) {
            return $matches[1];
        }

        if (preg_match('/mid=([A-Za-z0-9_-]+)/', $response->body(), $matches)) {
            return $matches[1];
        }

        return null;
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

        if ($request->has('findings_private')) {
            $payload['findings_private'] = $request->boolean('findings_private');
        }

        if ($request->filled('area')) {
            // The edit screen posts the polygon as a JSON string (same as the
            // create form); accept a decoded array too.
            $area = $request->input('area');
            $payload['area'] = is_string($area) ? json_decode($area, true) : $area;
        }

        try {
            $response = Http::withToken($this->apiToken($request))
                ->timeout(15)
                ->put($this->base()."/expeditions/{$id}", $payload);
        } catch (ConnectionException $e) {
            return response()->json([
                'message' => 'Serwer nie odpowiada. Spróbuj ponownie za chwilę.',
            ], 504);
        }

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

    public function startFindingsExport(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post($this->base()."/expeditions/{$id}/findings-export");

        if ($response->status() === 403) {
            return response()->json(['error' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Nie udało się rozpocząć eksportu.'], 500);
        }

        return response()->json($response->json());
    }

    public function findingsExportProgress(Request $request, int $id, string $exportId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}/findings-export/{$exportId}/progress");

        if ($response->failed()) {
            return response()->json([
                'percent' => 0,
                'message' => 'Nie udało się sprawdzić postępu eksportu.',
                'done' => false,
                'failed' => true,
            ], 200);
        }

        return response()->json($response->json());
    }

    public function findingsExportDownload(Request $request, int $id, string $exportId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get($this->base()."/expeditions/{$id}/findings-export/{$exportId}/download");

        if ($response->failed()) {
            return redirect()->route('expeditions.show', $id)
                ->withErrors(['export' => 'Nie udało się pobrać eksportu.']);
        }

        $disposition = $response->header('Content-Disposition')
            ?: 'attachment; filename="znaleziska-poszukiwania.pdf"';

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
        ]);
    }

    public function removeFinding(Request $request, int $id, int $findingId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete($this->base()."/expeditions/{$id}/findings/{$findingId}");

        return $this->passthrough($response);
    }

    /**
     * Leader-only switch of the private flag on a finding pinned to an
     * expedition that keeps participants' findings private.
     */
    public function updateFindingPrivacy(Request $request, int $id, int $findingId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->patch($this->base()."/expeditions/{$id}/findings/{$findingId}/privacy", [
                'is_private' => $request->boolean('is_private'),
            ]);

        return $this->passthrough($response);
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

    public function updateParticipantRole(Request $request, int $id, int $participant)
    {
        $request->validate(['role' => ['required', 'in:member,leader']]);

        $response = Http::withToken($this->apiToken($request))
            ->patch($this->base()."/expeditions/{$id}/participants/{$participant}/role", [
                'role' => $request->role,
            ]);

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
