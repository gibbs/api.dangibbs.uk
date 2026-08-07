<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformRdapAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\RdapRequest;
use Illuminate\Http\JsonResponse;

class RdapController extends Controller
{
    /**
     * RDAP Lookup
     *
     * RDAP lookup utility for domains, IPs, ASNs, entities and URLs.
     */
    public function __invoke(RdapRequest $request, PerformRdapAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
