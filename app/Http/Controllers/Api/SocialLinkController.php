<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\SocialLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function __construct(
        protected SocialLinkService $socialLinkService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (! $businessId) {
            return response()->json(['error' => 'business_id is required'], 400);
        }

        $business = Business::find($businessId);

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $links = $this->socialLinkService->getLinksForBusiness($business);

        return response()->json($links);
    }
}
