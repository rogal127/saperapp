<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {
            // Honeypot field filled in — silently pretend it worked so the bot doesn't adapt.
            return redirect()->route('login');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:poszukiwacz,naukowiec'],
        ]);

        $response = Http::post(config('services.api.url').'/auth/register', [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'role' => $request->role,
        ]);

        if ($response->failed()) {
            $errors = $response->json('errors') ?? ['email' => [$response->json('message') ?? 'Błąd rejestracji.']];

            return back()->withErrors($errors)->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put('api_token', $response->json('token'));
        $request->session()->put('api_user', $response->json('user'));

        return redirect()->route('home');
    }
}
