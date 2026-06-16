<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformUuidAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\UuidRequest;
use Illuminate\Http\JsonResponse;

class UuidController extends Controller
{
    /**
     * UUID Generator
     *
     * Generate a universally unique identifier (UUID).
     */
    public function __invoke(UuidRequest $request, PerformUuidAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
