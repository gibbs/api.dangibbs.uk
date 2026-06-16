<?php

use App\Services\Feeds\ActivityFeedService;

it('runs the command and exits with code 0', function () {
    $this->mock(ActivityFeedService::class)
        ->shouldReceive('cache')
        ->once();

    $this->artisan('feed:activity')->assertExitCode(0);
});
