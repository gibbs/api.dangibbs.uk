<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\RdapData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

class PerformRdapAction
{
    public function __construct(
        protected Factory $http,
        protected Repository $config
    ) {}

    /**
     * Execute the RDAP lookup action
     */
    public function execute(RdapData $data): array
    {
        $requestUrl = sprintf('%s/tools/rdap', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            /**
             * The RDAP lookup result as an array. When the `json` format is
             * set, this is returned as a decoded object.
             *
             * @example { "objectClassName": "domain", "handle": "2138514_DOMAIN_COM-VRSN" }
             */
            'output' => $this->parseOutput($data->format, $response['output']),
            /**
             * The command used to perform the current lookup
             *
             * @example /usr/bin/rdap --json -- google.com
             */
            'command' => $response['command'],
        ];
    }

    /**
     * Parse the raw returned output.
     */
    private function parseOutput(string $format, string $output): array
    {
        if ($format === 'json') {
            $decoded = json_decode($output, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return explode("\n", $output);
    }
}
