<?php

namespace App\Scrapers\Crawlers;

use App\Models\ScrapingJob;
use App\Scrapers\Observers\BusinessCrawler;
use App\Scrapers\Parsers\BusinessParser;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;

class YelpCrawler
{
    /**
     * Build the Yelp search start URL from a scraping job.
     */
    public static function buildSearchUrl(ScrapingJob $scrapingJob): string
    {
        return 'https://www.yelp.com/search?'.http_build_query([
            'find_desc' => $scrapingJob->keyword,
            'find_loc' => $scrapingJob->location,
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
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
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
            ->setCrawlProfile(new CrawlInternalUrls($scrapingJob->source === 'yelp'
                ? 'https://www.yelp.com'
                : 'https://www.yelp.com'))
            ->setMaximumDepth(config('scraper.max_depth', 2))
            ->setTotalCrawlLimit(config('scraper.max_pages', 50))
            ->setDelayBetweenRequests(BusinessParser::randomDelayMs())
            ->startCrawling(self::buildSearchUrl($scrapingJob));

        return $observer;
    }
}
