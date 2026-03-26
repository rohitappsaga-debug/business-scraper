<?php

namespace App\Jobs;

use App\Models\Business;
use App\Scrapers\Spiders\BusinessEnrichmentSpider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RoachPHP\Roach;
use Symfony\Component\DomCrawler\Crawler;
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

        if (! $business) {
            return;
        }

        if (! $business->website) {
            $this->discoverWebsite($business);
        }

        if (! $business->website) {
            Log::info("EnrichBusinessJob: No website found after discovery for {$business->name}. Skipping.");

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

    private function discoverWebsite(Business $business): void
    {
        $query = urlencode($business->name.' '.$business->city.' official website');
        $url = "https://www.google.com/search?q={$query}";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ])->get($url);

            if ($response instanceof \Illuminate\Http\Client\Promises\LazyPromise) {
                $response = $response->wait();
            }

            if ($response && $response->successful()) {
                $crawler = new Crawler($response->body());
                // Extract first organic result link that isn't Google internal
                // Google search results often have links in 'div.g' or h3 a
                $website = $crawler->filter('#search a')->each(function (Crawler $link) {
                    $href = $link->attr('href');
                    if (! $href || ! str_starts_with($href, 'http') || str_contains($href, 'google.com') || str_contains($href, 'webcache') || str_contains($href, 'youtube.com')) {
                        return null;
                    }

                    return $href;
                });

                $website = array_filter($website)[0] ?? null;

                if ($website) {
                    Log::info("EnrichBusinessJob: Discovered website for {$business->name}: {$website}");
                    $business->update(['website' => $website]);
                    $business->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::warning("EnrichBusinessJob: Website discovery failed for {$business->name}: ".$e->getMessage());
        }
    }
}
