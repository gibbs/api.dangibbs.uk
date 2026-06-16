<?php

namespace App\Console\Commands;

use App\Services\Feeds\ActivityFeedService;
use Illuminate\Console\Command;

class ActivityFeed extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'feed:activity';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Cache activity feed API requests to GitHub';

    /**
     * {@inheritDoc}
     */
    public function __construct(
        protected ActivityFeedService $activityFeedService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->activityFeedService->cache();

        return 0;
    }
}
