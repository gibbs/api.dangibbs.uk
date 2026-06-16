<?php

namespace App\Services\Feeds;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;

/**
 * Requests, processed and caches a GitHub commit feed
 */
class ActivityFeedService implements FeedServiceInterface
{
    public string $cacheKey = 'github_activity_feed';

    public function __construct(
        protected Repository $cache,
        protected Factory $http,
        protected \Illuminate\Contracts\Config\Repository $config,
    ) {}

    public function cache(): void
    {
        $data = $this->getRaw();
        $items = $this->getProcessed($data);

        $this->cache->forever($this->cacheKey, [
            'items' => $items,
        ]);
    }

    public function getCached(): ?array
    {
        $cached = $this->cache->get($this->cacheKey);

        return is_array($cached) ? $cached : null;
    }

    public function getRaw(): array
    {
        $user = $this->config->get('github.api_username');

        $response = $this->http->withHeaders([
            'content-type' => 'application/json',
        ])
            ->get('https://api.github.com/search/commits?', [
                'q' => sprintf('author:%1$s committer:%1$s user:%1$s is:public', $user),
                'sort' => 'author-date',
                'order' => 'desc',
                'page' => 1,
                'per_page' => 10,
            ])
            ->throw()
            ->json();

        return $response;
    }

    /**
     * Processed data from feed JSON array
     */
    protected function getProcessed(array $data): array
    {
        $items = [];

        foreach ($data['items'] as $item) {
            $items[] = [
                'author' => [
                    'avatar_url' => $item['author']['avatar_url'],
                    'login' => $item['author']['login'],
                ],
                'commit' => [
                    'author' => [
                        'date' => $item['commit']['author']['date'],
                    ],
                    'message' => $item['commit']['message'],
                    'url' => $item['commit']['url'],
                ],
                'repository' => [
                    'name' => $item['repository']['name'],
                    'html_url' => $item['repository']['html_url'],
                ],
                'html_url' => $item['html_url'],
                'sha' => $item['sha'],
            ];
        }

        return $items;
    }
}
