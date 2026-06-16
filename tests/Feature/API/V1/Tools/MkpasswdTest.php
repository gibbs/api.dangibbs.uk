<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/mkpasswd' => Http::response(json_fixture('tools_mkpasswd_service_response.json')),
    ]);

    $this->user = User::factory()->make();
    $this->validPayload = [
        'input' => 'Password1',
        'method' => 'sha512crypt',
        'salt' => 'ABCDEF12',
    ];
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/mkpasswd', $this->validPayload)
        ->assertUnauthorized();
});

it('hashes a password and returns the output and command', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', $this->validPayload)
        ->assertOk()
        ->assertJsonStructure(['output', 'command'])
        ->assertJsonPath('output', '$6$DJtFeNbCe5w1QUmk$Slu7NCsDDO3es8GC1V5C2s4lbbgGX51nNNT6fcR3BzheH5egb81H9J00ZSnmaslUBFqH2KFFlZWbr/DOzNOr91')
        ->assertJsonPath('command', '/usr/bin/mkpasswd Password1 --method=sha512crypt --rounds=0');
});

it('accepts sha256crypt as a valid method', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', array_merge($this->validPayload, ['method' => 'sha256crypt']))
        ->assertOk();
});

it('accepts scrypt as a valid method', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', ['input' => 'Password1', 'method' => 'scrypt'])
        ->assertOk();
});

it('accepts md5crypt as a valid method', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', array_merge($this->validPayload, ['method' => 'md5crypt', 'salt' => 'ABCDEF12']))
        ->assertOk();
});

it('returns 422 when the input is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['input']);

    $this->postJson('/v1/tools/mkpasswd', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['input']);
});

it('returns 422 when method is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['method']);

    $this->postJson('/v1/tools/mkpasswd', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['method']);
});

it('returns 422 for an invalid method', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', array_merge($this->validPayload, ['method' => 'bcrypt']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['method']);
});

it('returns 422 when input contains non-alphanumeric characters', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', array_merge($this->validPayload, ['input' => 'Pass word!']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['input']);
});

it('returns 422 for sha salt shorter than 8 characters', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', array_merge($this->validPayload, ['salt' => 'SHORT']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['salt']);
});

it('returns 422 when scrypt method is given a salt', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', ['input' => 'Password1', 'method' => 'scrypt', 'salt' => 'ABCDEF12'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['salt']);
});

it('sends the request to the service mkpasswd endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', $this->validPayload);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/mkpasswd'));
});

it('sends the correct payload to the service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/mkpasswd', $this->validPayload);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['input'] === 'Password1'
            && $body['method'] === 'sha512crypt'
            && $body['salt'] === 'ABCDEF12';
    });
});
