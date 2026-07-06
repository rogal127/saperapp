<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ApiAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = $request->session()->get('api_token');

        if ($token) {
            Http::withToken($token)->post(config('services.api.url').'/auth/logout');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withoutCookie(ApiAuthenticated::REMEMBER_COOKIE);
    }
}
