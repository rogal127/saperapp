<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function roleSession(): array
{
    return ['api_token' => 'tok-abc', 'api_user' => ['id' => 1, 'name' => 'Kierownik']];
}

it('forwards a participant role change to the api', function () {
    Http::fake([
        '*/expeditions/5/participants/9/role' => Http::response(['id' => 9, 'role' => 'leader'], 200),
    ]);

    $this->withSession(roleSession())
        ->patchJson('/api/expeditions/5/participants/9/role', ['role' => 'leader'])
        ->assertOk()
        ->assertJsonPath('role', 'leader');

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request['role'] === 'leader');
});

it('rejects an invalid role before calling the api', function () {
    Http::fake();

    $this->withSession(roleSession())
        ->patchJson('/api/expeditions/5/participants/9/role', ['role' => 'boss'])
        ->assertStatus(422);

    Http::assertNothingSent();
});

it('passes a 403 from the api through', function () {
    Http::fake([
        '*/expeditions/5/participants/9/role' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    $this->withSession(roleSession())
        ->patchJson('/api/expeditions/5/participants/9/role', ['role' => 'member'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Brak uprawnień.');
});
