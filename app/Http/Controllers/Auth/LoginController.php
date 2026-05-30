<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $response = Http::post(config('services.api.url') . '/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
            'device_name' => 'saperapp-mobile',
        ]);

        if ($response->failed()) {
            return back()->withErrors([
                'email' => $response->json('message') ?? 'Nieprawidłowy email lub hasło.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('api_token', $response->json('token'));
        $request->session()->put('api_user', $response->json('user'));

        return redirect()->intended(route('home'));
    }
}
