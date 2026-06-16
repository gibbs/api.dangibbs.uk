<?php

namespace App\Console\Commands;

use App\Services\Feeds\InsightsFeedService;
use Illuminate\Console\Command;

class InsightsFeed extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'feed:insights';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Cache insights data from API requests to GitHub';

    /**
     * {@inheritDoc}
     */
    public function __construct(
        protected InsightsFeedService $insightsFeedService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->insightsFeedService->cache();

        return 0;
    }
}
