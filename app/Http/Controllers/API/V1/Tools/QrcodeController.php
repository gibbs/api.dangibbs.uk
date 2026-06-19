<?php

namespace App\Http\Controllers\API\V1\Tools;

use App\Actions\V1\Tools\PerformQrcodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tools\QrcodeRequest;
use Illuminate\Http\JsonResponse;

class QrcodeController extends Controller
{
    /**
     * QR Code Generator
     *
     * Generate QR code images
     */
    public function __invoke(QrcodeRequest $request, PerformQrcodeAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return response()->json($result);
    }
}
