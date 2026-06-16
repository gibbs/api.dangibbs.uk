<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\MkpasswdData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

class PerformMkpasswdAction
{
    public function __construct(
        protected Factory $http,
        protected Repository $config
    ) {}

    /**
     * Execute the mkpasswd action
     */
    public function execute(MkpasswdData $data): array
    {
        $requestUrl = sprintf('%s/tools/mkpasswd', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            /**
             * The generated password
             *
             * @example $7$CU..../....XQYs0r.MlhFWkeaDqfEV.0$IX9YbKfEBptSd30hC6/vRHTI6TYlLU2r.RlbhM5cdo.
             */
            'output' => $response['output'],
            /**
             * The command used to generate the password
             *
             * @example /usr/bin/mkpasswd test --method=scrypt --rounds=0
             */
            'command' => $response['command'],
        ];
    }
}
