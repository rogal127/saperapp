<?php

use Illuminate\Support\Facades\Http;

it('lists users returned by the api and links to their profiles', function () {
    Http::fake([
        '*/users*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Anna Kowalska', 'avatar_url' => null],
                ['id' => 2, 'name' => 'Marek Nowak', 'avatar_url' => null],
            ],
            'current_page' => 1,
            'last_page' => 1,
            'total_users' => 2,
            'available_letters' => ['A', 'M'],
        ], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->get('/users');

    $response->assertOk();
    $response->assertSee('Anna Kowalska');
    $response->assertSee('Marek Nowak');
    $response->assertSee(route('users.show', 1), false);
    $response->assertSee(route('users.show', 2), false);
    $response->assertSee('2 użytkowników');
});

it('forwards the search query, page and letter to the api', function () {
    Http::fake([
        '*/users*' => Http::response(['data' => [], 'current_page' => 2, 'last_page' => 2], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->get('/users?q=kowal&page=2&letter=K');

    $response->assertOk();

    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/users?q=kowal&page=2&letter=K');
});

it('only renders clickable links for letters the api reports as available', function () {
    Http::fake([
        '*/users*' => Http::response([
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'total_users' => 1,
            'available_letters' => ['A'],
        ], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->get('/users');

    $response->assertOk();
    $response->assertSee(route('users.index', ['letter' => 'A']), false);
    $response->assertDontSee(route('users.index', ['letter' => 'B']), false);
});

it('redirects to login when the session has expired', function () {
    $response = $this->get('/users');

    $response->assertRedirect(route('login'));
});
