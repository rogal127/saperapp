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
        ], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->get('/users');

    $response->assertOk();
    $response->assertSee('Anna Kowalska');
    $response->assertSee('Marek Nowak');
    $response->assertSee(route('users.show', 1), false);
    $response->assertSee(route('users.show', 2), false);
});

it('forwards the search query and page to the api', function () {
    Http::fake([
        '*/users*' => Http::response(['data' => [], 'current_page' => 2, 'last_page' => 2], 200),
    ]);

    $response = $this->withSession(['api_token' => 'tok-abc'])->get('/users?q=kowal&page=2');

    $response->assertOk();

    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/users?q=kowal&page=2');
});

it('redirects to login when the session has expired', function () {
    $response = $this->get('/users');

    $response->assertRedirect(route('login'));
});
