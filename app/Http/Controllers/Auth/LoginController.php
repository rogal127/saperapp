<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ApiAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    /**
     * Number of minutes the "remember me" cookie stays valid (30 days).
     */
    private const REMEMBER_MINUTES = 60 * 24 * 30;

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $response = Http::post(config('services.api.url').'/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
            'device_name' => 'saperapp-mobile',
        ]);

        if ($response->failed()) {
            return back()->withErrors([
                'email' => $response->json('message') ?? 'Nieprawidłowy email lub hasło.',
            ])->onlyInput('email');
        }

        $token = $response->json('token');
        $user = $response->json('user');

        $request->session()->regenerate();
        $request->session()->put('api_token', $token);
        $request->session()->put('api_user', $user);
        $request->session()->flash('show_welcome_modal', true);

        $redirect = redirect()->intended(route('home'));

        if ($request->boolean('remember')) {
            // Persist the credentials in a long-lived (encrypted) cookie so an
            // idle break no longer logs the user out once the session expires.
            $redirect->withCookie(cookie(
                ApiAuthenticated::REMEMBER_COOKIE,
                json_encode(['token' => $token, 'user' => $user]),
                self::REMEMBER_MINUTES,
            ));
        } else {
            $redirect->withoutCookie(ApiAuthenticated::REMEMBER_COOKIE);
        }

        return $redirect;
    }
}
