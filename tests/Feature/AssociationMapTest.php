<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

// Layout zapisuje odznaczone komunikaty w bazie, więc widok mapy jej wymaga.
uses(RefreshDatabase::class);

function adminSession(): array
{
    return ['api_token' => 'tok-abc', 'api_user' => ['id' => 1, 'name' => 'Admin', 'is_admin' => true]];
}

function memberSession(): array
{
    return ['api_token' => 'tok-abc', 'api_user' => ['id' => 2, 'name' => 'Poszukiwacz', 'is_admin' => false]];
}

it('shows the associations map mode only to admins', function () {
    Http::fake(['*/findings/count' => Http::response(['count' => 5], 200)]);

    $this->withSession(adminSession())->get('/map')
        ->assertOk()
        ->assertSee('data-mode="associations"', false)
        ->assertSee('id="assoc-modal"', false);

    $this->withSession(memberSession())->get('/map')
        ->assertOk()
        ->assertDontSee('data-mode="associations"', false)
        ->assertDontSee('id="assoc-modal"', false);
});

it('proxies the association list for admins', function () {
    Http::fake([
        '*/associations' => Http::response([
            'data' => [
                ['id' => 7, 'name' => 'Stowarzyszenie Alfa', 'phone' => '600 100 200', 'is_known' => true, 'latitude' => 52.1, 'longitude' => 21.0],
            ],
        ], 200),
    ]);

    $response = $this->withSession(adminSession())->getJson('/api/associations');

    $response->assertOk()->assertJsonPath('data.0.name', 'Stowarzyszenie Alfa');
    Http::assertSent(fn ($request) => $request->url() === config('services.api.url').'/associations');
});

it('forbids non-admins from reading associations', function () {
    Http::fake();

    $this->withSession(memberSession())->getJson('/api/associations')->assertForbidden();

    Http::assertNothingSent();
});

it('forwards a new association to the api', function () {
    Http::fake(['*/associations' => Http::response(['id' => 9], 201)]);

    $response = $this->withSession(adminSession())->postJson('/api/associations', [
        'name' => 'Stowarzyszenie Beta',
        'phone' => '601 202 303',
        'is_known' => true,
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);

    $response->assertCreated();

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request['name'] === 'Stowarzyszenie Beta'
        && $request['phone'] === '601 202 303'
        && $request['is_known'] === true
        && $request['latitude'] == 52.2297);
});

it('rejects an association without a name', function () {
    Http::fake();

    $this->withSession(adminSession())->postJson('/api/associations', [
        'is_known' => true,
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ])->assertJsonValidationErrors(['name']);

    Http::assertNothingSent();
});

it('forbids non-admins from creating associations', function () {
    Http::fake();

    $this->withSession(memberSession())->postJson('/api/associations', [
        'name' => 'Stowarzyszenie Beta',
        'is_known' => false,
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ])->assertForbidden();

    Http::assertNothingSent();
});

it('forwards an association update without coordinates', function () {
    Http::fake(['*/associations/9' => Http::response(['data' => ['id' => 9]], 200)]);

    $this->withSession(adminSession())->putJson('/api/associations/9', [
        'name' => 'Nowa nazwa',
        'phone' => null,
        'is_known' => false,
    ])->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && $request['name'] === 'Nowa nazwa'
        && $request['is_known'] === false
        && ! array_key_exists('latitude', $request->data()));
});

it('forbids non-admins from deleting associations', function () {
    Http::fake();

    $this->withSession(memberSession())->deleteJson('/api/associations/9')->assertForbidden();

    Http::assertNothingSent();
});

it('lets admins delete an association', function () {
    Http::fake(['*/associations/9' => Http::response(['message' => 'ok'], 200)]);

    $this->withSession(adminSession())->deleteJson('/api/associations/9')->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'DELETE');
});
