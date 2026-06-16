<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformMkpasswdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\MkpasswdRequest;
use Illuminate\Http\JsonResponse;

class MkpasswdController extends Controller
{
    /**
     * mkpasswd
     *
     * Encrypt strings with the libc crypt(3) function via mkpasswd.
     */
    public function __invoke(MkpasswdRequest $request, PerformMkpasswdAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
