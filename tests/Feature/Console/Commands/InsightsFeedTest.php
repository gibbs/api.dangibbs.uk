<?php

use App\Services\Feeds\InsightsFeedService;

it('runs the command and exits with code 0', function () {
    $this->mock(InsightsFeedService::class)
        ->shouldReceive('cache')
        ->once();

    $this->artisan('feed:insights')->assertExitCode(0);
});
