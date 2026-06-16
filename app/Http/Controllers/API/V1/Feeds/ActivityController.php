<?php

namespace App\Http\Controllers\API\V1\Feeds;

use App\Http\Controllers\Controller;
use App\Services\Feeds\ActivityFeedService;
use App\Services\Feeds\InsightsFeedService;
use Illuminate\Http\JsonResponse;

final class ActivityController extends Controller
{
    public function __construct(
        protected ActivityFeedService $activityFeedService,
        protected InsightsFeedService $insightsFeedService,
    ) {}

    /**
     * Activity Feed
     *
     * GitHub activity and insights data feed for gibbs.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'activity' => $this->activityFeedService->getCached(),
            'insights' => $this->insightsFeedService->getCached(),
        ]);
    }
}
