<?php

namespace App\Jobs;

use App\Models\Business;
use App\Scrapers\Spiders\BusinessEnrichmentSpider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RoachPHP\Roach;
use Throwable;

class EnrichBusinessJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(public readonly int $businessId) {}

    public function handle(): void
    {
        $business = Business::find($this->businessId);

        if (! $business || ! $business->website) {
            return;
        }

        Log::info("EnrichBusinessJob: Starting enrichment for {$business->name}", [
            'id' => $business->id,
            'website' => $business->website,
        ]);

        try {
            Roach::startSpider(BusinessEnrichmentSpider::class, context: [
                'website' => $business->website,
                'business_id' => $business->id,
            ]);
        } catch (Throwable $e) {
            Log::error("EnrichBusinessJob: Failed for {$business->name}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
