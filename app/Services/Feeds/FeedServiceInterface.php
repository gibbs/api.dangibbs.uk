<?php

namespace App\Services\Feeds;

use Illuminate\Http\Client\HttpClientException;

interface FeedServiceInterface
{
    /**
     * The cache key
     */
    public string $cacheKey { get; }

    /**
     * Cache the feed
     */
    public function cache(): void;

    /**
     * Get a cached feed
     */
    public function getCached(): ?array;

    /**
     * Request and return a raw feed
     *
     * @throws HttpClientException
     */
    public function getRaw(): array;
}
