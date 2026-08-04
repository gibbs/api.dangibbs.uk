<?php

use App\Services\Puppet\LitmusImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    Http::fake([
        'https://raw.githubusercontent.com/puppetlabs/litmusimage/refs/heads/main/images.json' => Http::response(json_fixture('litmusimage_images.json')),
        'https://endoflife.date/api/v1/products/ubuntu' => Http::response(json_fixture('eol_ubuntu.json')),
        'https://endoflife.date/api/v1/products/centos-stream' => Http::response(json_fixture('eol_centos_stream.json')),
        'https://endoflife.date/api/v1/products/centos' => Http::response(json_fixture('eol_centos.json')),
        'https://endoflife.date/api/v1/products/sles' => Http::response(json_fixture('eol_sles.json')),
    ]);
});

/**
 * Caching
 */
it('stores the litmus image feed under the correct cache key', function () {
    app(LitmusImageService::class)->cache();

    expect(Cache::get('puppet_litmusimages'))->toBeArray()->toHaveKey('items');
});

it('groups every image returned from github by distro', function () {
    app(LitmusImageService::class)->cache();

    // litmusimage_images.json has 6 images across 5 distros (centos appears twice: plain + stream)
    expect(Cache::get('puppet_litmusimages')['items'])->toHaveCount(5);
});

it('returns null when nothing is cached', function () {
    expect(app(LitmusImageService::class)->getCached())->toBeNull();
});

it('returns cached data', function () {
    app(LitmusImageService::class)->cache();

    expect(app(LitmusImageService::class)->getCached())->toBeArray();
});

/**
 * Requests
 */
it('requests the litmusimage images list from github', function () {
    app(LitmusImageService::class)->getRaw();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'litmusimage/refs/heads/main/images.json'));
});

it('requests eol data once per distinct endoflife.date product', function () {
    app(LitmusImageService::class)->getRaw();

    // 1 github request + 4 distinct products (ubuntu, centos, centos-stream, sles)
    Http::assertSentCount(5);
});

it('does not request eol data for images with no mapped product', function () {
    app(LitmusImageService::class)->getRaw();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'products/windows'));
});

/**
 * Processing
 */
it('groups images under their distro with an image key and human-readable name', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $ubuntu = collect($groups)->firstWhere('image', 'ubuntu');

    expect($ubuntu['name'])->toBe('Ubuntu')
        ->and($ubuntu['items'])->toHaveCount(1)
        ->and($ubuntu['items'][0])->toHaveKeys(['tag', 'dockerfile', 'platforms', 'base_image', 'base_tag', 'eol'])
        ->and($ubuntu['items'][0]['tag'])->toBe('24.04');
});

it('collapses centos and centos stream tags into a single centos group', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $centos = collect($groups)->firstWhere('image', 'centos');

    expect($centos['items'])->toHaveCount(2)
        ->and(collect($centos['items'])->pluck('tag')->all())->toBe(['7', 'stream9']);
});

it('orders groups alphabetically by distro name', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    expect(collect($groups)->pluck('name')->all())->toBe([
        'CentOS',
        'Scientific Linux',
        'SLES',
        'Ubuntu',
        'Windows',
    ]);
});

it('attaches endoflife.date data for a standard image', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $ubuntu = collect($groups)->firstWhere('image', 'ubuntu');

    expect($ubuntu['items'][0]['eol'])->toBe([
        'cycle' => '24.04',
        'release_date' => '2024-04-25',
        'eol_from' => '2029-05-31',
        'is_eol' => false,
        'source' => 'endoflife.date',
    ]);
});

it('maps centos stream tags to the centos-stream product', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $centos = collect($groups)->firstWhere('image', 'centos');
    $stream = collect($centos['items'])->firstWhere('tag', 'stream9');

    expect($stream['eol']['cycle'])->toBe('9')
        ->and($stream['eol']['source'])->toBe('endoflife.date');
});

it('looks up sles eol data using the base_tag rather than the litmus tag', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $sles = collect($groups)->firstWhere('image', 'sles');

    expect($sles['items'][0]['eol']['cycle'])->toBe('15.5');
});

it('falls back to manual eol data for scientificlinux', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $sl = collect($groups)->firstWhere('image', 'scientificlinux');

    expect($sl['items'][0]['eol'])->toBe([
        'cycle' => '7',
        'release_date' => '2014-05-14',
        'eol_from' => '2024-06-30',
        'is_eol' => true,
        'source' => 'manual',
    ]);
});

it('returns null eol data for images with no known product or manual entry', function () {
    $raw = app(LitmusImageService::class)->getRaw();
    $groups = app(LitmusImageService::class)->getProcessed($raw);

    $unknown = collect($groups)->firstWhere('image', 'windows');

    expect($unknown['name'])->toBe('Windows')
        ->and($unknown['items'][0]['eol'])->toBeNull();
});
