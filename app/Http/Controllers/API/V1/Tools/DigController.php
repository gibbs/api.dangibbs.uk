<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformDigAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\DigRequest;
use Illuminate\Http\JsonResponse;

class DigController extends Controller
{
    /**
     * DNS Lookup
     *
     * Dig based DNS lookup utility.
     */
    public function __invoke(DigRequest $request, PerformDigAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
