<?php

namespace App\Scrapers\Crawlers;

use App\Models\ScrapingJob;
use App\Scrapers\Observers\BusinessCrawler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class DuckDuckGoCrawler
{
    /**
     * Build the DuckDuckGo search start URL.
     */
    public static function buildSearchUrl(ScrapingJob $scrapingJob): string
    {
        return 'https://duckduckgo.com/html/?'.http_build_query([
            'q' => $scrapingJob->keyword.' '.$scrapingJob->location,
        ]);
    }

    /**
     * Start the scrape for DuckDuckGo using direct Guzzle request.
     */
    public static function crawl(ScrapingJob $scrapingJob): BusinessCrawler
    {
        $observer = new BusinessCrawler($scrapingJob);
        $url = self::buildSearchUrl($scrapingJob);

        $client = new Client([
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 30,
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        $uri = new Uri($url);

        try {
            $response = $client->get($url);

            // Manually trigger the observer to keep architecture consistent
            $observer->crawled(
                $uri,
                $response
            );

        } catch (\Exception $e) {
            Log::error('DuckDuckGo search failed: ' . $e->getMessage());
        }

        return $observer;
    }
}
