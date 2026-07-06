<?php

use App\Http\Middleware\ApiAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

it('sets a long-lived remember cookie when "remember me" is checked', function () {
    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'tok-abc',
            'user' => ['id' => 1, 'name' => 'Tester'],
        ], 200),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'secret',
        'remember' => 'on',
    ]);

    $response->assertRedirect();
    $response->assertCookie(ApiAuthenticated::REMEMBER_COOKIE);
});

it('does not persist a remember cookie when "remember me" is unchecked', function () {
    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'tok-abc',
            'user' => ['id' => 1, 'name' => 'Tester'],
        ], 200),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $response->assertRedirect();
    $response->assertCookieExpired(ApiAuthenticated::REMEMBER_COOKIE);
});

it('restores the session from the remember cookie after it expires', function () {
    $request = Request::create('/findings/create', 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->cookies->set(ApiAuthenticated::REMEMBER_COOKIE, json_encode([
        'token' => 'tok-123',
        'user' => ['id' => 1, 'name' => 'Tester'],
    ]));

    $response = (new ApiAuthenticated)->handle($request, fn ($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
    expect($request->session()->get('api_token'))->toBe('tok-123');
});

it('redirects to login when there is no session and no remember cookie', function () {
    $request = Request::create('/findings/create', 'GET');
    $request->setLaravelSession(app('session.store'));

    $response = (new ApiAuthenticated)->handle($request, fn ($req) => response('ok'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('/login');
});

it('returns a 401 JSON payload for AJAX requests when the session is gone', function () {
    $request = Request::create('/findings', 'POST');
    $request->setLaravelSession(app('session.store'));
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $response = (new ApiAuthenticated)->handle($request, fn ($req) => response('ok'));

    expect($response->getStatusCode())->toBe(401);
    expect($response->headers->get('Content-Type'))->toContain('json');
});
