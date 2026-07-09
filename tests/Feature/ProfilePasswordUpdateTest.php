<?php

use Illuminate\Support\Facades\Http;

it('forwards a password change to the api and shows a success message', function () {
    Http::fake([
        '*/me/password' => Http::response(['message' => 'Hasło zostało zmienione.'], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->put('/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/me/password'
        && $request['current_password'] === 'old-password'
        && $request['password'] === 'new-password');
});

it('shows validation errors from the api when the current password is wrong', function () {
    Http::fake([
        '*/me/password' => Http::response([
            'message' => 'Dane są nieprawidłowe.',
            'errors' => ['current_password' => ['Podane hasło jest nieprawidłowe.']],
        ], 422),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->put('/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('current_password');
    expect(session('errors')->first('current_password'))->toBe('Podane aktualne hasło jest nieprawidłowe.');
});

it('validates password confirmation locally before calling the api', function () {
    Http::fake();

    $response = $this->withSession(['api_token' => 'tok-abc'])->put('/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertSessionHasErrors('password');
    Http::assertNothingSent();
});
