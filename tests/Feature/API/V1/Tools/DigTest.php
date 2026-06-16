<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/dig' => Http::response(json_fixture('tools_dig_service_response.json')),
    ]);

    $this->user = User::factory()->make();
    $this->validPayload = [
        'name' => 'example.com',
        'nameserver' => 'google',
        'types' => ['a'],
    ];
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/dig', $this->validPayload)
        ->assertUnauthorized();
});

it('performs a dns lookup and returns parsed records', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', $this->validPayload)
        ->assertOk()
        ->assertJsonStructure([
            'output' => [['name', 'ttl', 'tag', 'value', 'raw']],
            'command',
        ])
        ->assertJsonCount(2, 'output')
        ->assertJsonPath('output.0.name', 'example.com.')
        ->assertJsonPath('output.0.ttl', '300')
        ->assertJsonPath('output.0.tag', 'A')
        ->assertJsonPath('output.0.value', '172.66.147.243')
        ->assertJsonPath('output.1.value', '104.20.23.154');
});

it('returns the correct command', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', $this->validPayload)
        ->assertOk()
        ->assertJsonPath('command', '/usr/bin/dig +yaml +notcp +recurse +qr +time=5 +tries=1 +retry=0 @8.8.8.8 A example.com');
});

it('accepts all valid nameservers', function (string $nameserver) {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', array_merge($this->validPayload, [
        'nameserver' => $nameserver,
    ]))
        ->assertOk();
})->with(['cloudflare', 'google', 'quad9', 'opendns', 'comodo']);

it('accepts a query field instead of name', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['name']);
    $payload['query'] = 'example.com';

    $this->postJson('/v1/tools/dig', $payload)
        ->assertOk();
});

it('accepts multiple DNS types', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', array_merge($this->validPayload, ['types' => ['a', 'aaaa', 'mx']]))
        ->assertOk();
});

it('returns 422 when both the name and query are missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['name']);

    $this->postJson('/v1/tools/dig', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'query']);
});

it('returns 422 when the nameserver is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['nameserver']);

    $this->postJson('/v1/tools/dig', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nameserver']);
});

it('returns 422 for an invalid nameserver', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', array_merge($this->validPayload, ['nameserver' => 'invalid-ns']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nameserver']);
});

it('returns 422 for an invalid dns type', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', array_merge($this->validPayload, ['types' => ['invalid']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['types.0']);
});

it('sends the request to the service dig endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', $this->validPayload);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/dig'));
});

it('sends the correct payload to the tool service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/dig', $this->validPayload);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['name'] === 'example.com'
            && $body['nameserver'] === 'google'
            && ($body['types']['a'] ?? false) === true;
    });
});
