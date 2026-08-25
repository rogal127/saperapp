<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Przekazuje żądania mapy stowarzyszeń do API. Dostęp mają wyłącznie
 * administratorzy - pilnuje tego zarówno ten kontroler, jak i polityka po
 * stronie API.
 */
class AssociationController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    private function base(): string
    {
        return config('services.api.url');
    }

    private function abortUnlessAdmin(Request $request): void
    {
        abort_unless($request->session()->get('api_user.is_admin', false), 403);
    }

    public function index(Request $request)
    {
        $this->abortUnlessAdmin($request);

        $response = Http::withToken($this->apiToken($request))
            ->get($this->base().'/associations');

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }

    public function store(Request $request)
    {
        $this->abortUnlessAdmin($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_known' => ['required', 'boolean'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->post($this->base().'/associations', [
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'is_known' => $request->boolean('is_known'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

        if ($response->status() === 422) {
            return response()->json($response->json(), 422);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Nie udało się zapisać stowarzyszenia.'], 502);
        }

        return response()->json($response->json(), 201);
    }

    public function update(Request $request, int $associationId)
    {
        $this->abortUnlessAdmin($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_known' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $payload = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'is_known' => $request->boolean('is_known'),
        ];

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $payload['latitude'] = $request->input('latitude');
            $payload['longitude'] = $request->input('longitude');
        }

        $response = Http::withToken($this->apiToken($request))
            ->put($this->base()."/associations/{$associationId}", $payload);

        if ($response->status() === 422) {
            return response()->json($response->json(), 422);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Nie udało się zapisać zmian.'], 502);
        }

        return response()->json($response->json());
    }

    public function destroy(Request $request, int $associationId)
    {
        $this->abortUnlessAdmin($request);

        $response = Http::withToken($this->apiToken($request))
            ->delete($this->base()."/associations/{$associationId}");

        if ($response->failed()) {
            return response()->json(['message' => 'Nie udało się usunąć stowarzyszenia.'], 502);
        }

        return response()->json($response->json());
    }
}
