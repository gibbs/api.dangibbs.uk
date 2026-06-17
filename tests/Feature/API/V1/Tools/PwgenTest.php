<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/pwgen' => Http::response(json_fixture('tools_pwgen_service_response.json')),
    ]);

    $this->user = User::factory()->make();
    $this->validPayload = [
        'num-passwords' => 3,
        'length' => 32,
        'numerals' => true,
        'secure' => true,
        'symbols' => true,
        'no-numerals' => false,
        'no-capitalize' => false,
        'ambiguous' => false,
        'capitalize' => false,
        'no-vowels' => false,
        'remove-chars' => '',
    ];
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/pwgen', $this->validPayload)
        ->assertUnauthorized();
});

it('generates passwords and returns an array of outputs', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', $this->validPayload)
        ->assertOk()
        ->assertJsonStructure(['output', 'command'])
        ->assertJsonPath('output', [
            'Password1!',
            'Password2!',
            'Password3!',
        ])
        ->assertJsonPath('command', '/usr/bin/pwgen -1 --num-passwords=3 --numerals --secure --symbols 32');
});

it('returns 422 when num-passwords is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['num-passwords']);

    $this->postJson('/v1/tools/pwgen', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['num-passwords']);
});

it('returns 422 when length is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['length']);

    $this->postJson('/v1/tools/pwgen', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['length']);
});

it('returns 422 when num-passwords exceeds maximum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', array_merge($this->validPayload, ['num-passwords' => 101]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['num-passwords']);
});

it('returns 422 when the length exceeds maximum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', array_merge($this->validPayload, ['length' => 8193]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['length']);
});

it('returns 422 when num-passwords is below minimum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', array_merge($this->validPayload, ['num-passwords' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['num-passwords']);
});

it('sends the request to the service pwgen endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', $this->validPayload);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/pwgen'));
});

it('sends the correct payload to the service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', $this->validPayload);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['num-passwords'] === 3
            && $body['length'] === 32
            && $body['numerals'] === true
            && $body['secure'] === true
            && $body['symbols'] === true;
    });
});

it('defaults nullable fields to false or empty string', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/pwgen', [
        'num-passwords' => 3,
        'length' => 32,
    ]);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['no-numerals'] === false
            && $body['no-capitalize'] === false
            && $body['ambiguous'] === false
            && $body['capitalize'] === false
            && $body['numerals'] === false
            && $body['secure'] === false
            && $body['no-vowels'] === false
            && $body['symbols'] === false
            && $body['remove-chars'] === '';
    });
});
