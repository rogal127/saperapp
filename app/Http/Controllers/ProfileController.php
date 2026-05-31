<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    public function show(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . '/profile');

        $user = $response->successful() ? $response->json('data') : [];

        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:200'],
        ]);

        $parts     = explode(' ', trim($request->name), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? '';

        $response = Http::withToken($this->apiToken($request))
            ->put(config('services.api.url') . '/profile', [
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ]);

        if ($response->failed()) {
            return back()->withErrors(['name' => 'Błąd zapisu.'])->withInput();
        }

        return back()->with('success', 'Profil zaktualizowany!');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->attach('avatar', file_get_contents($request->file('avatar')->getRealPath()), $request->file('avatar')->getClientOriginalName())
            ->post(config('services.api.url') . '/profile/avatar');

        if ($response->failed()) {
            return back()->withErrors(['avatar' => 'Nie udało się przesłać zdjęcia.']);
        }

        return back()->with('success', 'Zdjęcie profilowe zaktualizowane!');
    }
}
