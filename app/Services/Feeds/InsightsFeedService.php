<?php

namespace App\Services\Feeds;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;

/**
 * Requests, processes and caches public GitHub language usage stats
 */
class InsightsFeedService implements FeedServiceInterface
{
    public string $cacheKey = 'github_insights_languages';

    public function __construct(
        protected Repository $cache,
        protected Factory $http,
        protected \Illuminate\Contracts\Config\Repository $config,
    ) {}

    public function cache(): void
    {
        $data = $this->getRaw();
        $languages = $this->getProcessed($data);

        $this->cache->forever($this->cacheKey, $languages);
    }

    public function getCached(): ?array
    {
        $cached = $this->cache->get($this->cacheKey);

        return is_array($cached) ? $cached : null;
    }

    public function getRaw(): array
    {
        $user = $this->config->get('github.api_username');
        $pass = $this->config->get('github.api_access_token');

        $repositories = $this->http->withBasicAuth($user, $pass)
            ->get('https://api.github.com/users/gibbs/repos', [
                'order' => 'desc',
                'page' => 1,
                'per_page' => 100,
            ])
            ->throw()
            ->json();

        $languages = [];

        foreach ($repositories as $repository) {
            // Ignore forks
            if ($repository['fork']) {
                continue;
            }

            // Get the repository language data
            $languages[] = $this->http->withBasicAuth($user, $pass)
                ->withHeaders([
                    'content-type' => 'application/json',
                ])
                ->get(sprintf('https://api.github.com/repos/%s/languages', $repository['full_name']))
                ->throw()
                ->json();
        }

        return [
            'repositories' => $repositories,
            'languages' => $languages,
        ];
    }

    /**
     * Process data from feeds
     */
    public function getProcessed(array $data): array
    {
        $processed = [
            'stats' => [
                'bytes' => 0,
                'count' => 0,
            ],
            'languages' => [],
        ];

        foreach ($data['languages'] as $response) {
            foreach ($response as $language => $bytes) {
                $processed['stats']['count'] += 1;
                $processed['stats']['bytes'] += $bytes;

                if (! array_key_exists($language, $processed['languages'])) {
                    $processed['languages'][$language] = [
                        'name' => $language,
                        'count' => 1,
                        'bytes' => $bytes,
                    ];

                    continue;
                }

                $processed['languages'][$language]['count'] += 1;
                $processed['languages'][$language]['bytes'] += $bytes;
            }
        }

        // Default sort by count
        uasort($processed['languages'], function ($x, $y) {
            return $y['count'] <=> $x['count'];
        });

        $topLanguage = array_key_first($processed['languages']);

        foreach ($processed['languages'] as $key => $language) {
            $processed['languages'][$key]['percentage'] = ceil($language['count'] / $processed['stats']['count'] * 100);

            $processed['languages'][$key]['width'] = ($key === $topLanguage)
                ? 100
                : ceil($language['count'] / $processed['languages'][$topLanguage]['count'] * 100);
        }

        return [
            'languages' => array_keys($processed['languages']),
            'stats' => $processed['stats'],
            'usage' => $processed['languages'],
        ];
    }
}
