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
            'city' => ['nullable', 'string', 'max:255'],
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
            'city' => $request->city,
            'depth_cm' => $request->depth_cm,
        ]);

        if ($response->failed()) {
            return back()->withErrors($response->json('errors') ?? ['name' => 'Błąd zapisu.'])->withInput();
        }

        return redirect()->route('home')->with('success', 'Znalezisko dodane!');
    }

    public function map()
    {
        return view('findings.map');
    }

    public function mapSearch(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . '/map/findings', [
                'lat' => $request->lat,
                'lng' => $request->lng,
                'radius' => $request->radius,
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Błąd API'], 502);
        }

        return response()->json($response->json('data'));
    }
}
