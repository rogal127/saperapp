<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $response = Http::post(config('services.api.url').'/auth/forgot-password', [
            'email' => $request->email,
        ]);

        if ($response->failed()) {
            $errors = $response->json('errors') ?? ['email' => [$response->json('message') ?? 'Nie udało się wysłać linku. Spróbuj ponownie za chwilę.']];

            return back()->withErrors($errors)->withInput();
        }

        return back()->with('status', $response->json('message'));
    }
}
