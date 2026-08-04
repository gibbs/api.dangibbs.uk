<?php

namespace App\Services\Puppet;

use App\Services\Feeds\FeedServiceInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Requests, processes and caches Puppet Litmus images with endoflife.date data
 */
class LitmusImageService implements FeedServiceInterface
{
    public string $cacheKey = 'puppet_litmusimages';

    /**
     * Maps a litmus image name to a distro name title.
     */
    protected const DISTRO_NAME_MAP = [
        'almalinux' => 'AlmaLinux',
        'amazonlinux' => 'Amazon Linux',
        'centos' => 'CentOS',
        'debian' => 'Debian',
        'fedora' => 'Fedora',
        'oraclelinux' => 'Oracle Linux',
        'redhat' => 'Red Hat Enterprise Linux',
        'rockylinux' => 'Rocky Linux',
        'scientificlinux' => 'Scientific Linux',
        'sles' => 'SLES',
        'ubuntu' => 'Ubuntu',
    ];

    /**
     * Maps a litmus image name to its endoflife.date product slug.
     */
    protected const EOL_DISTRO_MAP = [
        'almalinux' => 'almalinux',
        'amazonlinux' => 'amazon-linux',
        'centos' => 'centos',
        'debian' => 'debian',
        'fedora' => 'fedora',
        'oraclelinux' => 'oracle-linux',
        'redhat' => 'rhel',
        'rockylinux' => 'rocky-linux',
        'ubuntu' => 'ubuntu',
    ];

    /**
     * Manually maintained EOL data for images with no endoflife.date product.
     */
    protected const EOL_MANUAL = [
        'scientificlinux' => [
            '6' => [
                'cycle' => '6',
                'release_date' => '2011-05-10',
                'eol_from' => '2020-11-30',
            ],
            '7' => [
                'cycle' => '7',
                'release_date' => '2014-05-14',
                'eol_from' => '2024-06-30',
            ],
        ],
    ];

    public function __construct(
        protected Repository $cache,
        protected Factory $http,
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
        $products = [];
        $images = $this->http->withHeaders([
            'content-type' => 'application/json',
        ])
            ->get('https://raw.githubusercontent.com/puppetlabs/litmusimage/refs/heads/main/images.json')
            ->throw()
            ->json();

        foreach ($images as $image) {
            $product = $this->resolveLookup($image)['product'];

            if ($product === null || array_key_exists($product, $products)) {
                continue;
            }

            $products[$product] = $this->http->withHeaders([
                'content-type' => 'application/json',
            ])
                ->get("https://endoflife.date/api/v1/products/{$product}")
                ->throw()
                ->json('result.releases', []);
        }

        return [
            'images' => $images,
            'products' => $products,
        ];
    }

    /**
     * Litmus images with EOL datae
     */
    public function getProcessed(array $data): array
    {
        return collect($data['images'])
            ->groupBy('image')
            ->map(fn (Collection $images, string $image) => [
                'image' => $image,
                'name' => self::DISTRO_NAME_MAP[$image] ?? Str::title($image),
                'items' => $images->map(fn (array $item) => [
                    'tag' => $item['tag'],
                    'dockerfile' => $item['dockerfile'],
                    'platforms' => $item['platforms'],
                    'base_image' => $item['base_image'],
                    'base_tag' => $item['base_tag'],
                    'eol' => $this->resolveEol($item, $data['products']),
                ])->values()->all(),
            ])
            ->sortBy(fn (array $group) => Str::lower($group['name']))
            ->values()
            ->all();
    }

    /**
     * Resolve the endoflife.date product slug and release version.
     *
     * @return array{product: ?string, version: string}
     */
    protected function resolveLookup(array $image): array
    {
        // CentOS Stream tags (e.g. "stream8") are a separate product
        if ($image['image'] === 'centos' && str_starts_with($image['tag'], 'stream')) {
            return [
                'product' => 'centos-stream',
                'version' => substr($image['tag'], strlen('stream')),
            ];
        }

        // SLES releases are tracked by their full point release (e.g. "15.5")
        if ($image['image'] === 'sles') {
            return [
                'product' => 'sles',
                'version' => $image['base_tag'],
            ];
        }

        return [
            'product' => self::EOL_DISTRO_MAP[$image['image']] ?? null,
            'version' => $image['tag'],
        ];
    }

    /**
     * @param  array<string, array>  $products
     */
    protected function resolveEol(array $image, array $products): ?array
    {
        $lookup = $this->resolveLookup($image);

        if ($lookup['product'] === null) {
            $manual = self::EOL_MANUAL[$image['image']][$image['tag']] ?? null;

            if ($manual === null) {
                return null;
            }

            return [
                'cycle' => $manual['cycle'],
                'release_date' => $manual['release_date'],
                'eol_from' => $manual['eol_from'],
                'is_eol' => Carbon::parse($manual['eol_from'])->isPast(),
                'source' => 'manual',
            ];
        }

        $release = collect($products[$lookup['product']] ?? [])
            ->firstWhere('name', $lookup['version']);

        if ($release === null) {
            return null;
        }

        return [
            'cycle' => $release['name'],
            'release_date' => $release['releaseDate'] ?? null,
            'eol_from' => $release['eolFrom'] ?? null,
            'is_eol' => (bool) ($release['isEol'] ?? false),
            'source' => 'endoflife.date',
        ];
    }
}
