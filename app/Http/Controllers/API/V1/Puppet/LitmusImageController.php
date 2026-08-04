<?php

namespace App\Http\Controllers\API\V1\Puppet;

use App\Http\Controllers\Controller;
use App\Services\Puppet\LitmusImageService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Puppet')]
final class LitmusImageController extends Controller
{
    public function __construct(
        protected LitmusImageService $litmusImageService,
    ) {}

    /**
     * Litmus Images
     *
     * Puppet Litmus docker image data with end of life data.
     *
     * @response array{
     *     items: list<array{
     *         image: string,
     *         name: string,
     *         items: list<array{
     *             tag: string,
     *             dockerfile: string,
     *             platforms: list<string>,
     *             base_image: string,
     *             base_tag: string,
     *             eol: array{
     *                 cycle: string,
     *                 release_date: string|null,
     *                 eol_from: string|null,
     *                 is_eol: bool,
     *                 source: string
     *             }|null
     *         }>
     *     }>
     * }|null
     */
    public function __invoke(): JsonResponse
    {
        return response()->json($this->litmusImageService->getCached());
    }
}
