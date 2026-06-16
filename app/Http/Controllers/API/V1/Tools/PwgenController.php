<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformPwgenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\PwgenRequest;
use Illuminate\Http\JsonResponse;

class PwgenController extends Controller
{
    /**
     * Password Generator
     *
     * Create numerous memorable and pronounceable (or random) secure passwords using the pwgen utility.
     */
    public function __invoke(PwgenRequest $request, PerformPwgenAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
