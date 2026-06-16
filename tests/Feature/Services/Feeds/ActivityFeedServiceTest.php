<?php

use App\Services\Feeds\ActivityFeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    Http::fake([
        'https://api.github.com/search/commits*' => Http::response(json_fixture('github_commits_search.json')
        ),
    ]);
});

/**
 * Caching
 */
it('stores the activity feed under the correct cache key', function () {
    app(ActivityFeedService::class)->cache();

    expect(Cache::get('github_activity_feed'))->toBeArray();
});

it('stores all items returned from github', function () {
    app(ActivityFeedService::class)->cache();

    expect(Cache::get('github_activity_feed')['items'])->toHaveCount(2);
});

it('maps only the expected fields from each commit', function () {
    app(ActivityFeedService::class)->cache();

    $item = Cache::get('github_activity_feed')['items'][0];

    expect(array_keys($item))->toBe(['author', 'commit', 'repository', 'html_url', 'sha'])
        ->and(array_keys($item['author']))->toBe(['avatar_url', 'login'])
        ->and(array_keys($item['commit']))->toBe(['author', 'message', 'url'])
        ->and(array_keys($item['commit']['author']))->toBe(['date'])
        ->and(array_keys($item['repository']))->toBe(['name', 'html_url']);
});

/**
 * Processing
 */
it('returns null when nothing is cached', function () {
    expect(app(ActivityFeedService::class)->getCached())->toBeNull();
});

it('returns cached data', function () {
    app(ActivityFeedService::class)->cache();

    expect(app(ActivityFeedService::class)->getCached())->toBeArray();
});

/**
 * Requests
 */
it('sends a request to the github commits search endpoint', function () {
    app(ActivityFeedService::class)->getRaw();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.github.com/search/commits');
    });
});
