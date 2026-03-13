<?php

namespace App\Scrapers;

use App\Models\ScrapingJob;
use App\Scrapers\Crawlers\YellowPagesCrawler;
use App\Scrapers\Observers\BusinessCrawler;

class CrawlerFactory
{
    /**
     * Determine and start the appropriate crawler for the job.
     */
    public static function crawl(ScrapingJob $scrapingJob): BusinessCrawler
    {
        $location = strtolower($scrapingJob->location);

        // User requested Google Maps as the main source.
        // It's rich in data and works globally.
        if ($scrapingJob->source !== 'yellowpages_force') {
            return Crawlers\GoogleMapsCrawler::crawl($scrapingJob);
        }

        return YellowPagesCrawler::crawl($scrapingJob);
    }

    private static function isUSLocation(string $location): bool
    {
        // Simple US check: common states, zip codes, or "USA"
        $usPatterns = [
            '/\b(usa|united states|texas|california|ny|nyc|florida|tx|ca|fl)\b/i',
            '/\b\d{5}(-\d{4})?\b/', // Zip code
        ];

        foreach ($usPatterns as $pattern) {
            if (preg_match($pattern, $location)) {
                return true;
            }
        }

        return false;
    }
}
