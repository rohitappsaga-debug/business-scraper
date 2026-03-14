<?php

namespace App\Scrapers\Crawlers;

use App\Models\Business;
use App\Models\ScrapingJob;
use App\Scrapers\Observers\BusinessCrawler;
use App\Scrapers\Parsers\BusinessParser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

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
            $body = (string) $response->getBody();
            $page = new SymfonyCrawler($body);

            // 1. Initial parse
            $observer->crawled(new Uri($url), $response);

            // 2. Deep scrape: Find businesses missing phone/website and fetch details
            $businesses = Business::where('scraping_job_id', $scrapingJob->id)
                ->where(function ($q) {
                    $q->whereNull('phone')
                        ->orWhere('phone', '')
                        ->orWhereNull('website')
                        ->orWhere('website', '');
                })
                ->get();

            foreach ($businesses as $business) {
                if ($business->cid) {
                    try {
                        // CID format: 0x... or decimal
                        // Useful URL: https://www.google.com/search?q=place&ludocid={cid}
                        $detailUrl = 'https://www.google.com/maps?cid='.$business->cid.'&hl=en';

                        $detailResponse = $client->get($detailUrl);
                        $detailPage = new SymfonyCrawler((string) $detailResponse->getBody());

                        $details = BusinessParser::parseGoogleMapsDetailPage($detailPage);

                        // Update if we found new info
                        $business->update(array_filter([
                            'phone' => $details['phone'] ?: null,
                            'website' => $details['website'] ?: null,
                            'address' => $details['address'] ?: $business->address,
                        ]));

                        Log::info('Google Maps Deep Scrape Success', ['business_id' => $business->id, 'cid' => $business->cid]);

                        usleep(BusinessParser::randomDelayMs() * 1000);
                    } catch (\Exception $e) {
                        Log::debug('Google Maps Deep Scrape Detail Fetch Failed', ['id' => $business->id, 'error' => $e->getMessage()]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Google Maps Crawl Failed', [
                'job_id' => $scrapingJob->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $observer;
    }
}
