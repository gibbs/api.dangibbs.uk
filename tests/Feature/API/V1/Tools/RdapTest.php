<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/rdap' => Http::response(json_fixture('tools_rdap_service_response.json')),
    ]);

    $this->user = User::factory()->make();
    $this->validPayload = [
        'query' => 'google.com',
        'type' => 'domain',
        'format' => 'json',
    ];
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/rdap', $this->validPayload)
        ->assertUnauthorized();
});

it('performs an rdap lookup and returns the parsed domain output and command', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/v1/tools/rdap', $this->validPayload)
        ->assertOk()
        ->assertJsonStructure(['output', 'command'])
        ->assertJsonPath('command', '/usr/bin/rdap --json -- google.com');

    $domain = $response->json('output');

    expect($domain['objectClassName'])->toBe('domain');
    expect($domain['handle'])->toBe('2138514_DOMAIN_COM-VRSN');
    expect($domain['ldhName'])->toBe('GOOGLE.COM');
    expect($domain['status'])->toBe(['client delete prohibited', 'client transfer prohibited', 'client update prohibited']);
    expect($domain['nameservers'])->toHaveCount(2);
    expect($domain['nameservers'][0]['ldhName'])->toBe('NS1.GOOGLE.COM');
    expect($domain['entities'][0]['roles'])->toBe(['registrar']);
    expect($domain['secureDNS']['delegationSigned'])->toBeFalse();
});

it('returns 422 when the query is missing', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', ['format' => 'json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['query']);
});

it('accepts all valid formats', function (string $format) {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['format' => $format]))
        ->assertOk();
})->with(['text', 'whois', 'json', 'raw']);

it('returns the parsed json object as output when the format is json', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['format' => 'json']))
        ->assertOk();

    expect($response->json('output'))->toBeArray()->toHaveKey('objectClassName');
});

it('returns the raw output split into lines when the format is not json', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['format' => 'whois']))
        ->assertOk();

    expect($response->json('output'))->toBeArray()->each->toBeString();
});

it('returns 422 for an invalid format', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['format' => 'yaml']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['format']);
});

it('accepts all valid types', function (string $type) {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['type' => $type]))
        ->assertOk();
})->with(['autnum', 'domain', 'entity', 'help', 'ip', 'nameserver', 'url']);

it('returns 422 for an invalid type', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['type' => 'invalid']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('accepts a server override', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['server' => 'https://rdap.verisign.com/com/v1/']))
        ->assertOk();
});

it('accepts a list of fields', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['fields' => ['handle', 'status']]))
        ->assertOk();
});

it('returns 422 when a field is not a string', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, ['fields' => [123]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fields.0']);
});

it('does not accept a verbose, timeout, insecure or experimental parameter', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, [
        'verbose' => true,
        'timeout' => 30,
        'insecure' => true,
        'experimental' => true,
    ]));

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['verbose'] === false
            && $body['timeout'] === 10
            && $body['insecure'] === false
            && $body['experimental'] === false;
    });
});

it('sends the request to the service rdap endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', $this->validPayload);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/rdap'));
});

it('sends the correct payload to the tool service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', array_merge($this->validPayload, [
        'server' => 'https://rdap.verisign.com/com/v1/',
        'fields' => ['handle'],
    ]));

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['query'] === 'google.com'
            && $body['verbose'] === false
            && $body['timeout'] === 10
            && $body['insecure'] === false
            && $body['experimental'] === false
            && $body['format'] === 'json'
            && $body['server'] === 'https://rdap.verisign.com/com/v1/'
            && $body['type'] === 'domain'
            && $body['fields'] === ['handle'];
    });
});

it('defaults the format to text when not provided', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/rdap', ['query' => 'google.com']);

    Http::assertSent(fn ($request) => ($request->data()['format'] ?? null) === 'text');
});
