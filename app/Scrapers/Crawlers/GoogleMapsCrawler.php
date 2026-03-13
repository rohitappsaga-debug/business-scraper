<?php

namespace App\Scrapers\Crawlers;

use App\Models\ScrapingJob;
use App\Scrapers\Observers\BusinessCrawler;
use App\Scrapers\Parsers\BusinessParser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class GoogleMapsCrawler
{
    public static function crawl(ScrapingJob $scrapingJob): BusinessCrawler
    {
        $keyword = $scrapingJob->keyword;
        $location = $scrapingJob->location;

        $query = urlencode("{$keyword} in {$location}");
        $url = "https://www.google.com/search?tbm=lcl&q={$query}&hl=en";

        Log::info('Google Maps (Local Search) Crawl Started', [
            'url' => $url,
            'job_id' => $scrapingJob->id,
        ]);

        $client = new Client([
            RequestOptions::VERIFY => false,
            RequestOptions::HEADERS => [
                'User-Agent' => BusinessParser::randomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://www.google.com/',
            ],
            RequestOptions::TIMEOUT => 30,
        ]);

        $observer = new BusinessCrawler($scrapingJob);

        try {
            $response = $client->get($url);

            // Re-use Spatie's interface-like call manually or just pass it to observer
            // Since we aren't using Spatie Crawler (no Node.js), we trigger observer manually
            $dummyUri = new Uri($url);
            $observer->crawled($dummyUri, $response);

        } catch (\Exception $e) {
            Log::error('Google Maps Crawl Failed', [
                'job_id' => $scrapingJob->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $observer;
    }
}
