<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/uuidgen' => Http::response(json_fixture('tools_uuid_service_response.json')),
    ]);

    $this->user = User::factory()->make();
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/uuid')
        ->assertUnauthorized();
});

it('generates a uuid with default options', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/uuid', [])
        ->assertOk()
        ->assertJsonStructure(['output', 'command'])
        ->assertJsonPath('output', '659127d3-b934-4574-8008-4c318298dca5')
        ->assertJsonPath('command', '/usr/bin/uuidgen --random');
});

it('generates a random uuid', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/uuid', ['random' => true, 'time' => false])
        ->assertOk()
        ->assertJsonPath('output', '659127d3-b934-4574-8008-4c318298dca5');
});

it('rejects a non-boolean random value', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/uuid', ['random' => 'not-a-bool'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['random']);
});

it('rejects a non-boolean time value', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/uuid', ['time' => 'not-a-bool'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['time']);
});

it('sends the request to the service uuid endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/uuid', [
        'random' => true,
        'time' => false,
    ]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/uuidgen'));
});
