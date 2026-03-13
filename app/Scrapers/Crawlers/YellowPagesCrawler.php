<?php

namespace App\Scrapers\Crawlers;

use App\Models\ScrapingJob;
use App\Scrapers\Observers\BusinessCrawler;
use App\Scrapers\Parsers\BusinessParser;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;

class YellowPagesCrawler
{
    /**
     * Build the YellowPages search start URL from a scraping job.
     */
    public static function buildSearchUrl(ScrapingJob $scrapingJob): string
    {
        // YP search URL format: https://www.yellowpages.com/search?search_terms=Dentist&geo_location_terms=Texas
        return 'https://www.yellowpages.com/search?'.http_build_query([
            'search_terms' => $scrapingJob->keyword,
            'geo_location_terms' => $scrapingJob->location,
        ]);
    }

    /**
     * Start the Spatie crawler for the given scraping job.
     */
    public static function crawl(ScrapingJob $scrapingJob): BusinessCrawler
    {
        $observer = new BusinessCrawler($scrapingJob);

        $clientOptions = [
            RequestOptions::HEADERS => [
                'User-Agent' => BusinessParser::randomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Ch-Ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'DNT' => '1',
                'Referer' => 'https://www.yellowpages.com/',
            ],
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::VERIFY => false,
            RequestOptions::ALLOW_REDIRECTS => [
                'max' => 5,
                'strict' => false,
            ],
        ];

        $proxy = config('scraper.proxy');
        if (! empty($proxy)) {
            $clientOptions[RequestOptions::PROXY] = $proxy;
        }

        Crawler::create($clientOptions)
            ->setCrawlObserver($observer)
            ->setCrawlProfile(new CrawlInternalUrls('https://www.yellowpages.com'))
            ->setMaximumDepth(config('scraper.max_depth', 2))
            ->setTotalCrawlLimit(config('scraper.max_pages', 20)) // Limit YP pages
            ->setDelayBetweenRequests(BusinessParser::randomDelayMs())
            ->startCrawling(self::buildSearchUrl($scrapingJob));

        return $observer;
    }
}
