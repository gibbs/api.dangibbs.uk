<?php

use App\Services\Puppet\LitmusImageService;

it('returns the cached litmus image feed', function () {
    $this->mock(LitmusImageService::class)
        ->shouldReceive('getCached')
        ->once()
        ->andReturn(['items' => [
            [
                'image' => 'ubuntu',
                'tag' => '24.04',
            ],
        ]]);

    $this->getJson('/v1/puppet/litmusimages')
        ->assertOk()
        ->assertExactJson(['items' => [['image' => 'ubuntu', 'tag' => '24.04']]]);
});

it('returns null when nothing is cached yet', function () {
    $this->mock(LitmusImageService::class)
        ->shouldReceive('getCached')
        ->once()
        ->andReturn(null);

    $response = $this->getJson('/v1/puppet/litmusimages')->assertOk();

    expect($response->getContent())->toBe('{}');
});
