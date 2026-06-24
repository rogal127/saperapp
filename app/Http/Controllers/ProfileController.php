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
        $token = $this->apiToken($request);
        $base = config('services.api.url');

        $userResponse = Http::withToken($token)->get("{$base}/me");
        $consentsResponse = Http::withToken($token)->get("{$base}/wkz-consents");

        $user = $userResponse->successful() ? ($userResponse->json('data') ?? $userResponse->json()) : [];
        $wkzConsents = $consentsResponse->successful() ? $consentsResponse->json() : [];

        $grouped = [];
        if (! empty($user['id'])) {
            $findingsResponse = Http::withToken($token)->get("{$base}/users/{$user['id']}");

            if ($findingsResponse->successful()) {
                foreach ($findingsResponse->json('findings') ?? [] as $finding) {
                    $voi = $finding['voivodeship'] ?? 'Nieznane';
                    $cou = $finding['county'] ?? 'Nieznane';
                    $cit = $finding['city'] ?? 'Nieznana';
                    $grouped[$voi][$cou][$cit][] = $finding;
                }
                ksort($grouped);
            }
        }

        return view('profile.index', compact('user', 'wkzConsents', 'grouped'));
    }

    public function storeWkzConsent(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $response = Http::withToken($this->apiToken($request))
            ->post(config('services.api.url').'/wkz-consents', [
                'name' => $request->name,
            ]);

        if ($response->failed()) {
            return back()->withErrors(['wkz_name' => 'Nie udało się dodać zgody.'])->withInput();
        }

        return back()->with('success', 'Zgoda dodana!')->withFragment('wkz-consents');
    }

    public function destroyWkzConsent(Request $request, int $id)
    {
        Http::withToken($this->apiToken($request))
            ->delete(config('services.api.url')."/wkz-consents/{$id}");

        return back()->with('success', 'Zgoda usunięta.')->withFragment('wkz-consents');
    }

    public function wkzConsentReport(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/wkz-consents/{$id}/report");

        if ($response->failed()) {
            return back()->withErrors(['report' => 'Nie udało się wygenerować raportu.']);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $response->header('Content-Disposition') ?? 'attachment; filename="raport-wkz.pdf"',
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:poszukiwacz,naukowiec'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->put(config('services.api.url').'/me', [
                'name' => $request->name,
                'role' => $request->role,
            ]);

        if ($response->failed()) {
            return back()->withErrors(['name' => 'Błąd zapisu.'])->withInput();
        }

        return back()->with('success', 'Profil zaktualizowany!');
    }

    public function startFindingsExport(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->post(config('services.api.url').'/findings-export');

        if ($response->failed()) {
            return response()->json(['error' => 'Nie udało się rozpocząć eksportu.'], 500);
        }

        return response()->json($response->json());
    }

    public function findingsExportProgress(Request $request, string $exportId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings-export/{$exportId}/progress");

        return response()->json($response->json());
    }

    public function findingsExportDownload(Request $request, string $exportId)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url')."/findings-export/{$exportId}/download");

        if ($response->failed()) {
            return back()->withErrors(['export' => 'Nie udało się pobrać eksportu.']);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="moje-znaleziska.pdf"',
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->attach(
                'avatar',
                file_get_contents($request->file('avatar')->getRealPath()),
                $request->file('avatar')->getClientOriginalName()
            )
            ->post(config('services.api.url').'/me/avatar');

        if ($response->failed()) {
            return back()->withErrors(['avatar' => 'Nie udało się przesłać zdjęcia.']);
        }

        return back()->with('success', 'Zdjęcie profilowe zaktualizowane!');
    }
}
