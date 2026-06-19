<?php

namespace App\Actions\V1\Tools;

use App\DataTransferObjects\V1\Tools\QrcodeData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;

class PerformQrcodeAction
{
    public function __construct(
        protected Factory $http,
        protected Repository $config
    ) {}

    /**
     * Execute the qrcode action
     */
    public function execute(QrcodeData $data): array
    {
        $requestUrl = sprintf('%s/tools/qrencode', $this->config->get('app.tool_service_url'));

        $response = $this->http->withHeaders(['content-type' => 'application/json'])
            ->post($requestUrl, $data->toArray())
            ->throw()
            ->json();

        return [
            /**
             * The generated QR code image encoded in Base64
             *
             * @example iVBORw0KGgoAAAANSUhEUgAAAGMAAABjAQMAAAC19SzWAAAABlBMVEUAAAD///+l2Z/dAAAAAnRSTlP//8i138cAAAAJcEhZcwAACxIAAAsSAdLdfvwAAADqSURBVDiNzdSxDcMgEAXQs1zQJQsgsQadV/ItEOIJWImONZBYADoKy5dLZMlpnEMpolDxCkuffxig9wV/rAIww+oCDJIqrdhWjLyRFPRtyhQBO+Sa8X3Cpl2PaJ0VvCU7FZ8Po8bjtKfiVabsjwZPVVSa7XgHs8gynswSsqjN6oviIAkl1UBL0ACZJHEWUAnD3sQHbZA3la78taSi9AXI71k+iSJ3kAuMJKnYNLRnZ17Saw6mkqmSeLaOeLyyXvdaD9Qhvtdc2z5bQS6m26SvHcLGEx4XUZwl5mINSXr+03aslKukX79E3+kB1cy2O6nZ2JsAAAAASUVORK5CYII=
             */
            'output' => explode("\n", $response['output']),
            /**
             * The command used to generate the current QR code
             *
             * @example /usr/bin/qrencode --type=png -o - https://google.com
             */
            'command' => $response['command'],
        ];
    }
}
