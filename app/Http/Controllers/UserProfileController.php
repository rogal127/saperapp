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

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . '/users/' . $id);

        if ($response->failed()) {
            abort(404);
        }

        $profile = $response->json();

        $grouped = [];
        foreach ($profile['findings'] ?? [] as $finding) {
            $voi = $finding['voivodeship'] ?? 'Nieznane';
            $cou = $finding['county'] ?? 'Nieznane';
            $cit = $finding['city'] ?? 'Nieznana';
            $grouped[$voi][$cou][$cit][] = $finding;
        }
        ksort($grouped);

        $currentUser = $request->session()->get('api_user', []);
        $currentUserId = $currentUser['id'] ?? null;

        return view('users.show', [
            'profile'       => $profile,
            'grouped'       => $grouped,
            'currentUserId' => $currentUserId,
        ]);
    }
}
