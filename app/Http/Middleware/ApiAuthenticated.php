<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticated
{
    /**
     * Name of the long-lived "remember me" cookie holding the API token.
     */
    public const REMEMBER_COOKIE = 'api_remember';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('api_token') && ! $this->restoreFromRememberCookie($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sesja wygasła. Zaloguj się ponownie.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }

    /**
     * Restore the API token/user into the session from the remember-me cookie.
     */
    private function restoreFromRememberCookie(Request $request): bool
    {
        $raw = $request->cookie(self::REMEMBER_COOKIE);

        if (! $raw) {
            return false;
        }

        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['token'])) {
            return false;
        }

        $request->session()->put('api_token', $data['token']);
        $request->session()->put('api_user', $data['user'] ?? null);

        return true;
    }
}
