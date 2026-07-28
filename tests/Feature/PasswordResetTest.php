<?php

use Illuminate\Support\Facades\Http;

it('shows the forgot password form', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('Nie pamiętasz hasła?');
});

it('links to the forgot password page from the login form', function () {
    $this->get('/login')->assertOk()->assertSee(route('password.request'));
});

it('forwards a reset link request to the api and shows the confirmation', function () {
    Http::fake([
        '*/auth/forgot-password' => Http::response(['message' => 'Jeśli konto o podanym adresie istnieje, wysłaliśmy na nie link do zresetowania hasła.'], 200),
    ]);

    $response = $this->post('/forgot-password', ['email' => 'ktos@example.com']);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/auth/forgot-password'
        && $request['email'] === 'ktos@example.com');
});

it('validates the email locally before calling the api', function () {
    Http::fake();

    $this->post('/forgot-password', ['email' => 'nie-email'])->assertSessionHasErrors('email');

    Http::assertNothingSent();
});

it('shows the reset form with the token and prefilled email', function () {
    $response = $this->get('/reset-password/token-abc?email=ktos%40example.com');

    $response->assertOk();
    $response->assertSee('token-abc', false);
    $response->assertSee('ktos@example.com', false);
});

it('forwards a password reset to the api and redirects to login', function () {
    Http::fake([
        '*/auth/reset-password' => Http::response(['message' => 'Hasło zostało zmienione. Możesz się teraz zalogować.'], 200),
    ]);

    $response = $this->post('/reset-password', [
        'token' => 'token-abc',
        'email' => 'ktos@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');

    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/auth/reset-password'
        && $request['token'] === 'token-abc'
        && $request['password'] === 'new-password');
});

it('shows api validation errors when the reset token is invalid', function () {
    Http::fake([
        '*/auth/reset-password' => Http::response([
            'message' => 'Dane są nieprawidłowe.',
            'errors' => ['email' => ['Link do resetowania hasła jest nieprawidłowy lub wygasł.']],
        ], 422),
    ]);

    $response = $this->post('/reset-password', [
        'token' => 'stary-token',
        'email' => 'ktos@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe('Link do resetowania hasła jest nieprawidłowy lub wygasł.');
});

it('validates the new password locally before calling the api', function () {
    Http::fake();

    $this->post('/reset-password', [
        'token' => 'token-abc',
        'email' => 'ktos@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'inne-haslo',
    ])->assertSessionHasErrors('password');

    Http::assertNothingSent();
});
