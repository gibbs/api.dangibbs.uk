<?php

use App\Services\Puppet\LitmusImageService;

it('runs the command and exits with code 0', function () {
    $this->mock(LitmusImageService::class)
        ->shouldReceive('cache')
        ->once();

    $this->artisan('feed:litmusimage')->assertExitCode(0);
});
