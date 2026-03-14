<?php

namespace App\Scrapers\Observers;

use App\Jobs\ExtractEmailsJob;
use App\Models\Business;
use App\Models\ScrapingJob;
use App\Scrapers\Parsers\BusinessParser;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Symfony\Component\DomCrawler\Crawler;

class BusinessCrawler extends CrawlObserver
{
    private int $savedCount = 0;

    public function __construct(private readonly ScrapingJob $scrapingJob) {}

    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        $body = (string) $response->getBody();
        $urlStr = (string) $url;

        if (empty($body)) {
            return;
        }

        $page = new Crawler($body);

        if (str_contains($urlStr, 'yellowpages.com')) {
            // Check if it's a detail page vs search results
            // Detail pages usually have /bus-name/lid-12345 or /info-XXXXXXXX
            if (preg_match('/\/[^\/]+\/lid-\d+/', $urlStr) || str_contains($urlStr, '/info-')) {
                $this->processYellowPagesDetailPage($page, $urlStr);
            } else {
                $this->parseSearchResultsPage($page, $urlStr);
            }
        } elseif (str_contains($urlStr, 'google.com')) {
            $this->parseGoogleMapsResults($page);
        } else {
            // General fallback
            $this->parseSearchResultsPage($page, $urlStr);
        }

        usleep(BusinessParser::randomDelayMs() * 1000);
    }

    private function processYellowPagesDetailPage(Crawler $page, string $url): void
    {
        try {
            $data = BusinessParser::parseYellowPagesDetailPage($page);
            if (! empty($data['name'])) {
                $this->saveBusiness($data);
            }
        } catch (Exception $e) {
            Log::debug('Failed to parse YP detail page', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        Log::warning('Crawl failed', [
            'url' => (string) $url,
            'error' => $requestException->getMessage(),
            'job_id' => $this->scrapingJob->id,
        ]);
    }

    public function getSavedCount(): int
    {
        return $this->savedCount;
    }

    /**
     * Parse the search results page and extract business cards.
     */
    private function parseSearchResultsPage(Crawler $page, string $url): void
    {

        if (str_contains($url, 'google.com')) {
            $this->parseGoogleMapsResults($page);

            return;
        }

        // YellowPages search result cards
        $selectors = [
            '.v-card',
            '.result',
            '.info',
            'div[class*="v-card"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $cards = $page->filter($selector);
                if ($cards->count() > 0) {
                    $cards->each(function (Crawler $card) {
                        $this->processBusinessCard($card);
                    });

                    return;
                }
            } catch (Exception) {
                continue;
            }
        }

        // Fallback: try parsing any h3/h4 business name links
        $this->parseFallbackListings($page);
    }

    /**
     * Parse Google Local Search results (tbm=lcl).
     */
    private function parseGoogleMapsResults(Crawler $page): void
    {
        // Google Local Search result containers
        $selectors = [
            'div.uMdZh',    // Observation from debug HTML
            'div.VkpSyc',   // Alternative packed layout
            'a[data-cid]',  // Links with Client IDs
            'div[data-cid]', // Any div with a CID
            'div[role="article"]',
            'div.Nv2Wbe',
            'div.fontBodyMedium',
            'div[class*="listing"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $nodes = $page->filter($selector);
                if ($nodes->count() > 0) {
                    $nodes->each(function (Crawler $node) {
                        try {
                            $data = BusinessParser::parseGoogleMapsResult($node);
                            if (! empty($data['name'])) {
                                $this->saveBusiness($data);
                            }
                        } catch (Exception $e) {
                            Log::debug('Failed to parse Google Maps node', ['error' => $e->getMessage()]);
                        }
                    });

                    return;
                }
            } catch (Exception) {
                continue;
            }
        }

        // Fallback to searching for structured lists
        $this->parseFallbackListings($page);
    }

    private function processBusinessCard(Crawler $card): void
    {
        try {
            $data = BusinessParser::parseYellowPagesCard($card);

            if (empty($data['name'])) {
                return;
            }

            $this->saveBusiness($data);
        } catch (Exception $e) {
            Log::debug('Failed to parse business card', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fallback parser for pages that don't match the standard Yelp card selectors.
     */
    private function parseFallbackListings(Crawler $page): void
    {
        try {
            $page->filter('h3 a, h4 a')->each(function (Crawler $link) {
                $name = trim($link->text(''));
                if (! empty($name) && strlen($name) > 3) {
                    $this->saveBusiness([
                        'name' => $name,
                        'category' => '',
                        'address' => '',
                        'city' => $this->scrapingJob->location,
                        'state' => '',
                        'phone' => '',
                        'website' => '',
                        'rating' => null,
                        'reviews_count' => null,
                    ]);
                }
            });
        } catch (Exception) {
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBusiness(array $data): void
    {
        $name = (string) ($data['name'] ?? '');
        $rawAddress = (string) ($data['address'] ?? '');

        if (empty($name)) {
            return;
        }

        // 1. Clean data BEFORE anything else (centralized helper handles phone stripping)
        $cleaned = BusinessParser::cleanGoogleAddress($rawAddress, $data['phone'] ?? null);

        // 2. Truncate AFTER cleaning to ensure we had the full string for regex matching
        $name = mb_substr($name, 0, 191);
        $address = mb_substr($cleaned['address'], 0, 191);

        $hash = Business::generateDedupHash($name, $address);

        $business = Business::where('dedup_hash', $hash)->first();

        $updateData = [
            'scraping_job_id' => $this->scrapingJob->id,
            'name' => $name,
            'category' => $data['category'] ?? ($business->category ?? null),
            'address' => $address,
            'city' => ($data['city'] ?: $cleaned['city']) ?: $this->scrapingJob->location,
            'state' => ($data['state'] ?: $cleaned['state']) ?: ($business->state ?? null),
            'zip' => ($data['zip'] ?: $cleaned['zip']) ?: ($business->zip ?? null),
            'country' => ($data['country'] ?? $cleaned['country']) ?? ($business->country ?? $this->deriveCountryFromLocation()),
            'phone' => $cleaned['phone'],
            'website' => $data['website'] ?: ($business->website ?? null),
            'rating' => $data['rating'] ?? ($business->rating ?? null),
            'reviews_count' => $data['reviews_count'] ?? ($business->reviews_count ?? null),
            'latitude' => $data['latitude'] ?? ($business->latitude ?? null),
            'longitude' => $data['longitude'] ?? ($business->longitude ?? null),
            'cid' => $data['cid'] ?? ($business->cid ?? null),
            'source' => $this->scrapingJob->source,
            'dedup_hash' => $hash,
        ];

        if (empty($updateData['city'])) {
            $updateData['city'] = $cleaned['city'];
        }
        if (empty($updateData['state'])) {
            $updateData['state'] = $cleaned['state'];
        }
        if (empty($updateData['zip'])) {
            $updateData['zip'] = $cleaned['zip'];
        }
        if (empty($updateData['country'])) {
            $updateData['country'] = $cleaned['country'] ?? $this->deriveCountryFromLocation();
        }

        if ($business) {
            $business->update($updateData);
        } else {
            $business = Business::create($updateData);
        }

        $this->savedCount++;

        // Trigger email extraction if website exists and we haven't found emails yet
        if (! empty($business->website) && $business->businessEmails()->count() === 0) {
            ExtractEmailsJob::dispatch($business)->onQueue('default');
        }
    }

    /**
     * Attempt to derive country from the scraping job's location.
     */
    private function deriveCountryFromLocation(): ?string
    {
        $location = trim($this->scrapingJob->location);

        if (empty($location)) {
            return null;
        }

        // Standardized mapping for known locations/countries
        $mappings = [
            'Dubai' => 'United Arab Emirates',
            'Abu Dhabi' => 'United Arab Emirates',
            'UAE' => 'United Arab Emirates',
            'India' => 'India',
            'Surat' => 'India',
            'Mumbai' => 'India',
            'Delhi' => 'India',
            'Bangalore' => 'India',
            'Pune' => 'India',
            'Ahmedabad' => 'India',
            'USA' => 'United States',
            'United States' => 'United States',
            'UK' => 'United Kingdom',
            'United Kingdom' => 'United Kingdom',
            'London' => 'United Kingdom',
            'Tokyo' => 'Japan',
            'Japan' => 'Japan',
            'Paris' => 'France',
            'France' => 'France',
            'Berlin' => 'Germany',
            'Germany' => 'Germany',
            'Canada' => 'Canada',
            'Toronto' => 'Canada',
            'Australia' => 'Australia',
            'Sydney' => 'Australia',
            'Singapore' => 'Singapore',
        ];

        foreach ($mappings as $key => $country) {
            if (stripos($location, $key) !== false) {
                return $country;
            }
        }

        // If location contains a comma, the last part might be the country
        $parts = array_map('trim', explode(',', $location));
        if (count($parts) > 1) {
            $lastPart = end($parts);
            // Basic validation: country names usually don't have numbers
            if (! preg_match('/\d/', $lastPart) && strlen($lastPart) > 2) {
                return $lastPart;
            }
        }

        return $location; // Fallback to the location itself if nothing else matches
    }
}
