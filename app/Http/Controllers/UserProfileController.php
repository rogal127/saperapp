<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserProfileController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    public function index(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/users', $request->only(['q', 'page', 'letter']));

        if ($response->failed()) {
            abort(502);
        }

        $data = $response->json();

        return view('users.index', [
            'users' => $data['data'] ?? [],
            'currentPage' => $data['current_page'] ?? 1,
            'lastPage' => $data['last_page'] ?? 1,
            'totalUsers' => $data['total_users'] ?? 0,
            'availableLetters' => $data['available_letters'] ?? [],
            'query' => (string) $request->query('q', ''),
            'letter' => (string) $request->query('letter', ''),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/users/'.$id);

        if ($response->failed()) {
            abort(404);
        }

        $profile = $response->json();

        $regions = $profile['regions'] ?? [];
        ksort($regions);

        $currentUser = $request->session()->get('api_user', []);
        $currentUserId = $currentUser['id'] ?? null;

        return view('users.show', [
            'profile' => $profile,
            'regions' => $regions,
            'currentUserId' => $currentUserId,
        ]);
    }

    /**
     * Findings for a single voivodeship/county/city, fetched lazily once the
     * profile page's accordion is expanded down to that location. Shared by
     * both the own-profile and other-user-profile views.
     */
    public function findings(Request $request, int $id)
    {
        $request->validate([
            'voivodeship' => ['required', 'string'],
            'county' => ['required', 'string'],
            'city' => ['required', 'string'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/users/{$id}/findings", $request->only(['voivodeship', 'county', 'city']));

        if ($response->failed()) {
            return response()->json(['findings' => []], $response->status());
        }

        return response()->json($response->json());
    }

    public function destroy(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->delete(config('services.api.url').'/users/'.$id);

        if ($response->status() === 403) {
            return response()->json(['message' => 'Brak uprawnień.'], 403);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Nie udało się usunąć użytkownika.'], 502);
        }

        return response()->json(['message' => 'Użytkownik usunięty.']);
    }
}
