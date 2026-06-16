<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\PwgenData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

class PerformPwgenAction
{
    public function __construct(
        protected Factory $http,
        protected Repository $config
    ) {}

    /**
     * Execute the pwgen action
     */
    public function execute(PwgenData $data): array
    {
        $requestUrl = sprintf('%s/tools/pwgen', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            /**
             * The generated password(s)
             *
             * @example ["7fPO)<yX4Y]9K$dg'3q=rAQ{qxQQEvtz"]
             */
            'output' => explode("\n", $response['output']),
            /**
             * The command used to generate the current UUID
             *
             * @example /usr/bin/pwgen -1 --num-passwords=5 --numerals --secure --symbols 32
             */
            'command' => $response['command'],
        ];
    }
}
