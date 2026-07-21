<?php

use Illuminate\Support\Facades\Http;

it('shows the welcome modal right after logging in', function () {
    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'tok-abc',
            'user' => ['id' => 1, 'name' => 'Tester'],
        ], 200),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'secret',
    ])->assertRedirect(route('home'));

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Witaj w świecie Historius!');
    $response->assertSee('https://info.historius.pl', escape: false);
});

it('does not show the welcome modal on a page visited without just logging in', function () {
    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'tok-abc',
            'user' => ['id' => 1, 'name' => 'Tester'],
        ], 200),
    ]);

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'secret',
    ])->assertRedirect(route('home'));

    $this->get(route('home'));
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Witaj w świecie Historius!');
});
