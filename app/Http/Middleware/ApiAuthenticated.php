<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('api_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
