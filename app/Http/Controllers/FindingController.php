<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FindingController extends Controller
{
    public function create()
    {
        return view('findings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'depth_cm' => ['required', 'integer', 'min:0', 'max:9999'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('findings', 'public');
        }

        $user = $request->user() ?? User::where('email', 'test@example.com')->first();
        $user->findings()->create($data);

        return redirect()->route('home')->with('success', 'Znalezisko dodane!');
    }

    public function map()
    {
        return view('findings.map');
    }

    public function apiSearch(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $findings = Finding::withinRadiusCollection($request->lat, $request->lng, $request->radius)
            ->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,
                'latitude' => $f->latitude,
                'longitude' => $f->longitude,
                'depth_cm' => $f->depth_cm,
                'finder' => $f->user->name,
                'photo_url' => $f->photo_path ? Storage::url($f->photo_path) : null,
                'found_at' => $f->created_at->format('d.m.Y'),
                'distance' => round($f->distance, 2),
            ]);

        return response()->json($findings);
    }
}
