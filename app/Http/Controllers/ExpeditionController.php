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
