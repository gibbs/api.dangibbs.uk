<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        '*/tools/qrencode' => Http::response(json_fixture('tools_qrencode_service_response.json')),
    ]);

    $this->user = User::factory()->make();
    $this->validPayload = [
        'input' => 'https://google.com',
        'type' => 'PNG',
        'level' => 'L',
        'margin' => 4,
        'dpi' => 72,
        'background' => 'FFFFFF',
        'foreground' => '000000',
        'size' => 3,
        'ignorecase' => false,
        'casesensitive' => true,
    ];
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/v1/tools/qrcode', $this->validPayload)
        ->assertUnauthorized();
});

it('generates a qr code and returns the output and command', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', $this->validPayload)
        ->assertOk()
        ->assertJsonStructure(['output', 'command'])
        ->assertJsonPath('output', [
            'iVBORw0KGgoAAAANSUhEUgAAAGMAAABjAQMAAAC19SzWAAAABlBMVEUAAAD///+l2Z/dAAAAAnRSTlP//8i138cAAAAJcEhZcwAACxIAAAsSAdLdfvwAAADqSURBVDiNzdSxDcMgEAXQs1zQJQsgsQadV/ItEOIJWImONZBYADoKy5dLZMlpnEMpolDxCkuffxig9wV/rAIww+oCDJIqrdhWjLyRFPRtyhQBO+Sa8X3Cpl2PaJ0VvCU7FZ8Po8bjtKfiVabsjwZPVVSa7XgHs8gynswSsqjN6oviIAkl1UBL0ACZJHEWUAnD3sQHbZA3la78taSi9AXI71k+iSJ3kAuMJKnYNLRnZ17Saw6mkqmSeLaOeLyyXvdaD9Qhvtdc2z5bQS6m26SvHcLGEx4XUZwl5mINSXr+03aslKukX79E3+kB1cy2O6nZ2JsAAAAASUVORK5CYII=',
        ])
        ->assertJsonPath('command', '/usr/bin/qrencode --type=PNG --margin=4 --size=3 --dpi=72 --level=L --casesensitive --foreground=000000 --background=FFFFFF -o - https://google.com');
});

it('returns 422 when input is missing', function () {
    Sanctum::actingAs($this->user);

    $payload = $this->validPayload;
    unset($payload['input']);

    $this->postJson('/v1/tools/qrcode', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['input']);
});

it('returns 422 when input exceeds the maximum length', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['input' => str_repeat('a', 2049)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['input']);
});

it('accepts all valid types', function (string $type) {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['type' => $type]))
        ->assertOk();
})->with(['png', 'svg', 'eps', 'ascii', 'xpm']);

it('returns 422 for an invalid type', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['type' => 'jpg']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('accepts a lowercase type and sends it uppercased to the service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['type' => 'svg']))
        ->assertOk();

    Http::assertSent(fn ($request) => ($request->data()['type'] ?? null) === 'SVG');
});

it('accepts all valid error correction levels', function (string $level) {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['level' => $level]))
        ->assertOk();
})->with(['L', 'M', 'Q', 'H']);

it('returns 422 for an invalid level', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['level' => 'Z']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['level']);
});

it('accepts a lowercase level and sends it uppercased to the service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['level' => 'm']))
        ->assertOk();

    Http::assertSent(fn ($request) => ($request->data()['level'] ?? null) === 'M');
});

it('returns 422 when margin is below the minimum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['margin' => -1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['margin']);
});

it('returns 422 when margin exceeds the maximum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['margin' => 21]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['margin']);
});

it('returns 422 when dpi is below the minimum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['dpi' => 71]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dpi']);
});

it('returns 422 when dpi exceeds the maximum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['dpi' => 1201]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dpi']);
});

it('returns 422 when size is below the minimum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['size' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['size']);
});

it('returns 422 when size exceeds the maximum', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['size' => 51]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['size']);
});

it('returns 422 for an invalid background colour', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['background' => 'GGGGGG']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['background']);
});

it('returns 422 for an invalid foreground colour', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['foreground' => 'GGGGGG']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['foreground']);
});

it('accepts a 3 character hex colour for background and foreground', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, [
        'background' => 'FFF',
        'foreground' => '000',
    ]))->assertOk();
});

it('returns 422 when casesensitive is not a boolean', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['casesensitive' => 'not-a-bool']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['casesensitive']);
});

it('returns 422 when ignorecase is not a boolean', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', array_merge($this->validPayload, ['ignorecase' => 'not-a-bool']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ignorecase']);
});

it('sends the request to the service qrencode endpoint', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', $this->validPayload);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tools/qrencode'));
});

it('sends the correct payload to the service', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/v1/tools/qrcode', $this->validPayload);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['input'] === 'https://google.com'
            && $body['type'] === 'PNG'
            && $body['level'] === 'L'
            && $body['margin'] === 4
            && $body['dpi'] === 72
            && $body['background'] === 'FFFFFF'
            && $body['foreground'] === '000000'
            && $body['size'] === 3
            && $body['casesensitive'] === true
            && $body['ignorecase'] === false;
    });
});
