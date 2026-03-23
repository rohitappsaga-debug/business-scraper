<?php

namespace App\Services;

use App\Models\Business;
use App\Models\SocialLink;

class SocialLinkService
{
    public function getLinksForBusiness(Business $business): array
    {
        // Only Level 1: Database (Real links confirmed during scraping or manual entry)
        return SocialLink::where('business_id', $business->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($link) => [
                'platform' => $link->platform,
                'url' => $link->url,
            ])
            ->toArray();
    }

    private function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return str_replace('www.', '', $host);
    }
}
