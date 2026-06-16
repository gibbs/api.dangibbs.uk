<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\DigData;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as Http;
use Symfony\Component\Yaml\Yaml;

class PerformDigAction
{
    public function __construct(
        protected Http $http,
        protected Config $config
    ) {}

    /**
     * Execute the Dig lookup action
     */
    public function execute(DigData $data): array
    {
        $requestUrl = sprintf('%s/tools/dig', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            'output' => $this->parseResponseJson($response),
            /**
             * @example dig /usr/bin/dig +yaml +notcp +recurse +qr +time=5 +tries=1 +retry=0 @8.8.8.8 A google.com AAAA google.com
             */
            'command' => sprintf('%s', $response['command']),
        ];
    }

    /**
     * Parse and manipulate the response data.
     */
    private function parseResponseJson(array $response): array
    {
        $output = str_replace(['!!timestamp'], '', $response['output']);
        $digData = Yaml::parse($output);
        $records = [];

        foreach ($digData as $returned) {
            if (($returned['type'] ?? null) !== 'MESSAGE') {
                continue;
            }

            $answer = $returned['message']['response_message_data']['ANSWER_SECTION'] ?? null;

            if (! is_array($answer)) {
                continue;
            }

            foreach ($answer as $record) {
                $parts = explode(' ', $record);
                if (count($parts) < 4) {
                    continue;
                }

                $value = implode(' ', array_slice($parts, 4));

                $records[] = [
                    // @example google.com
                    'name' => (string) $parts[0],
                    // @example 235
                    'ttl' => (string) $parts[1],
                    // @example A
                    'tag' => (string) $parts[3],
                    // @example 142.250.151.113
                    'value' => (string) $value,
                    // @example google.com. 235 IN A 142.250.151.113
                    'raw' => (string) $record,
                ];
            }
        }

        return $records;
    }
}
