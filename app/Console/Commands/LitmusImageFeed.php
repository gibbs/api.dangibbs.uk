<?php

namespace App\Console\Commands;

use App\Services\Puppet\LitmusImageService;
use Illuminate\Console\Command;

class LitmusImageFeed extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'feed:litmusimage';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Cache Puppet Litmus image feed with end of life data';

    /**
     * {@inheritDoc}
     */
    public function __construct(
        protected LitmusImageService $litmusImageService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->litmusImageService->cache();

        return 0;
    }
}
