<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

// Layout zapisuje odznaczone komunikaty w bazie, więc widoki jej wymagają.
uses(RefreshDatabase::class);

function leaderSession(): array
{
    return ['api_token' => 'tok-abc', 'api_user' => ['id' => 1, 'name' => 'Kierownik']];
}

function expeditionPayload(bool $isLeader = true): array
{
    return [
        'data' => [
            'id' => 5,
            'name' => 'Rajd nad Wisłą',
            'description' => 'Zbiórka o 8:00',
            'area' => ['type' => 'MultiPolygon', 'coordinates' => [[[[21.0, 52.0], [21.1, 52.0], [21.1, 52.1], [21.0, 52.0]]]]],
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-08-02',
            'status' => 'published',
            'visibility' => 'private',
            'is_leader' => $isLeader,
        ],
    ];
}

it('shows the edit form to the leader with current values', function () {
    Http::fake(['*/expeditions/5' => Http::response(expeditionPayload(), 200)]);

    $this->withSession(leaderSession())->get('/expeditions/5/edit')
        ->assertOk()
        ->assertSee('Edytuj poszukiwanie')
        ->assertSee('value="Rajd nad Wisłą"', false)
        ->assertSee('value="2026-08-01"', false)
        ->assertSee('Zbiórka o 8:00');
});

it('forbids editing an expedition the user does not lead', function () {
    Http::fake(['*/expeditions/5' => Http::response(expeditionPayload(isLeader: false), 200)]);

    $this->withSession(leaderSession())->get('/expeditions/5/edit')->assertForbidden();
});

it('links to the edit screen from the expedition page of the leader', function () {
    Http::fake(['*/expeditions/5' => Http::response(expeditionPayload(), 200)]);

    $this->withSession(leaderSession())->get('/expeditions/5')
        ->assertOk()
        ->assertSee('/expeditions/5/edit', false);
});

it('hides the edit link from participants', function () {
    Http::fake(['*/expeditions/5' => Http::response(expeditionPayload(isLeader: false), 200)]);

    $this->withSession(leaderSession())->get('/expeditions/5')
        ->assertOk()
        ->assertDontSee('/expeditions/5/edit', false);
});

it('forwards edited details to the api', function () {
    Http::fake(['*/expeditions/5' => Http::response(['data' => ['id' => 5]], 200)]);

    $this->withSession(leaderSession())->putJson('/api/expeditions/5', [
        'name' => 'Nowa nazwa',
        'description' => 'Nowy opis',
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-03',
        'visibility' => 'public',
        'status' => 'finished',
    ])->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && $request['name'] === 'Nowa nazwa'
        && $request['visibility'] === 'public'
        && $request['status'] === 'finished'
        && ! array_key_exists('area', $request->data()));
});

it('forwards the private findings switch to the api', function () {
    Http::fake(['*/expeditions/5' => Http::response(['data' => ['id' => 5]], 200)]);

    $this->withSession(leaderSession())->putJson('/api/expeditions/5', [
        'name' => 'Rajd nad Wisłą',
        'findings_private' => true,
    ])->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'PUT' && $request['findings_private'] === true);
});

it('shows the private findings switch checked on the edit form', function () {
    $payload = expeditionPayload();
    $payload['data']['findings_private'] = true;
    Http::fake(['*/expeditions/5' => Http::response($payload, 200)]);

    $this->withSession(leaderSession())->get('/expeditions/5/edit')
        ->assertOk()
        ->assertSee('name="findings_private" value="1" class="w-5 h-5 accent-amber-500 shrink-0" checked', false);
});

it('forwards a finding privacy change to the api', function () {
    Http::fake(['*/expeditions/5/findings/9/privacy' => Http::response(['data' => ['id' => 9, 'is_private' => false]], 200)]);

    $this->withSession(leaderSession())->patchJson('/api/expeditions/5/findings/9/privacy', ['is_private' => false])
        ->assertOk()
        ->assertJsonPath('data.is_private', false);

    Http::assertSent(fn ($request) => $request->method() === 'PATCH' && $request['is_private'] === false);
});

it('decodes a redrawn area before forwarding it', function () {
    Http::fake(['*/expeditions/5' => Http::response(['data' => ['id' => 5]], 200)]);

    $area = ['type' => 'MultiPolygon', 'coordinates' => [[[[21.0, 52.0], [21.1, 52.0], [21.1, 52.1], [21.0, 52.0]]]]];

    $this->withSession(leaderSession())->putJson('/api/expeditions/5', [
        'name' => 'Rajd nad Wisłą',
        'area' => json_encode($area),
    ])->assertOk();

    // Loose comparison: whole coordinates come back from json_decode as ints.
    Http::assertSent(fn ($request) => is_array($request['area']) && $request['area'] == $area);
});

it('passes api validation errors back to the edit screen', function () {
    Http::fake([
        '*/expeditions/5' => Http::response(['errors' => ['ends_at' => ['Zła data.']]], 422),
    ]);

    $this->withSession(leaderSession())->putJson('/api/expeditions/5', [
        'name' => 'Rajd',
        'ends_at' => '2020-01-01',
    ])->assertStatus(422)->assertJsonPath('errors.ends_at.0', 'Zła data.');
});
