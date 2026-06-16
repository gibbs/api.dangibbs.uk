<?php

use App\Services\Feeds\InsightsFeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    Http::fake([
        'https://api.github.com/users/gibbs/repos*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/github_repos.json')), true)
        ),
        'https://api.github.com/repos/*/languages' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/github_repo_languages.json')), true)
        ),
    ]);
});

/**
 * Caching
 */
it('stores the insights feed under the correct cache key', function () {
    app(InsightsFeedService::class)->cache();

    expect(Cache::get('github_insights_languages'))->toBeArray()
        ->toHaveKeys(['languages', 'stats', 'usage']);
});

it('returns null when nothing is cached', function () {
    expect(app(InsightsFeedService::class)->getCached())->toBeNull();
});

it('returns cached data', function () {
    app(InsightsFeedService::class)->cache();

    expect(app(InsightsFeedService::class)->getCached())->toBeArray();
});

/**
 * Requests
 */
it('does not request language data for forked repositories', function () {
    app(InsightsFeedService::class)->getRaw();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'forked-repo');
    });
});

it('requests language data for each non-forked repository', function () {
    app(InsightsFeedService::class)->getRaw();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'repos/gibbs/repo1/languages'));
    Http::assertSent(fn ($r) => str_contains($r->url(), 'repos/gibbs/repo2/languages'));
});

/**
 * Processing
 */
it('returns repositories and languages from the github api', function () {
    $raw = app(InsightsFeedService::class)->getRaw();

    expect($raw)->toHaveKeys(['repositories', 'languages'])
        ->and($raw['repositories'])->toHaveCount(3)
        ->and($raw['languages'])->toHaveCount(2);
});

it('aggregates language bytes and counts across repositories', function () {
    $raw = app(InsightsFeedService::class)->getRaw();
    $result = app(InsightsFeedService::class)->getProcessed($raw);

    expect($result['usage']['PHP']['count'])->toBe(2)
        ->and($result['usage']['PHP']['bytes'])->toBe(100000)
        ->and($result['usage']['JavaScript']['count'])->toBe(2)
        ->and($result['usage']['JavaScript']['bytes'])->toBe(50000)
        ->and($result['usage']['Shell']['count'])->toBe(2)
        ->and($result['usage']['Shell']['bytes'])->toBe(10000);
});

it('calculates total stats across all language entries', function () {
    $raw = app(InsightsFeedService::class)->getRaw();
    $result = app(InsightsFeedService::class)->getProcessed($raw);

    expect($result['stats']['count'])->toBe(6)
        ->and($result['stats']['bytes'])->toBe(160000);
});

it('calculates language percentages', function () {
    $raw = app(InsightsFeedService::class)->getRaw();
    $result = app(InsightsFeedService::class)->getProcessed($raw);

    foreach ($result['usage'] as $language) {
        expect($language['percentage'])->toEqual(34);
    }
});

it('sets the top language width to 100', function () {
    $raw = app(InsightsFeedService::class)->getRaw();
    $result = app(InsightsFeedService::class)->getProcessed($raw);

    $top = $result['languages'][0];

    expect($result['usage'][$top]['width'])->toEqual(100);
});

it('lists language names in order', function () {
    $raw = app(InsightsFeedService::class)->getRaw();
    $result = app(InsightsFeedService::class)->getProcessed($raw);

    expect($result['languages'])->toContain('PHP')
        ->toContain('JavaScript')
        ->toContain('Shell');
});
