<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\UuidData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

class PerformUuidAction
{
    public function __construct(
        protected Factory $http,
        protected Repository $config
    ) {}

    /**
     * Execute the UUID action
     */
    public function execute(UuidData $data): array
    {
        $requestUrl = sprintf('%s/tools/uuidgen', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            /**
             * The generated UUID
             *
             * @example 38c34c76-6651-11f1-8bf8-c65d187c9c15
             */
            'output' => $response['output'],
            /**
             * The command used to generate the current UUID
             *
             * @example /usr/bin/uuidgen --random
             */
            'command' => $response['command'],
        ];
    }
}
