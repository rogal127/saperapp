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

    public function create()
    {
        return view('findings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city'     => ['nullable', 'string', 'max:255'],
            'city_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'city_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'voivodeship' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $pending = Http::withToken($this->apiToken($request));

        if ($request->hasFile('photo')) {
            $pending = $pending->attach(
                'photo',
                file_get_contents($request->file('photo')->getRealPath()),
                $request->file('photo')->getClientOriginalName()
            );
        }

        $response = $pending->post(config('services.api.url') . '/findings', [
            'name' => $request->name,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'city'        => $request->city,
            'city_lat'    => $request->city_lat,
            'city_lng'    => $request->city_lng,
            'voivodeship' => $request->voivodeship,
            'county' => $request->county,
            'depth_cm' => $request->depth_cm,
        ]);

        if ($response->failed()) {
            return back()->withErrors($response->json('errors') ?? ['name' => 'Błąd zapisu.'])->withInput();
        }

        return redirect()->route('home')->with('success', 'Znalezisko dodane!');
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . "/findings/{$id}");

        if ($response->status() === 404) {
            abort(404);
        }

        if ($response->failed()) {
            abort(502);
        }

        return view('findings.show', ['finding' => $response->json()]);
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
            ->post(config('services.api.url') . "/findings/{$findingId}/message", [
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
            'zoom'   => ['required', 'integer', 'min:0', 'max:19'],
            'sw_lat' => ['required', 'numeric'],
            'sw_lng' => ['required', 'numeric'],
            'ne_lat' => ['required', 'numeric'],
            'ne_lng' => ['required', 'numeric'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . '/map/clusters', $request->only('zoom', 'sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'));

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json());
    }
}
