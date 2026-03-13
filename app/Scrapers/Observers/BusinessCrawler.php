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

        if (empty($body)) {
            return;
        }

        $page = new Crawler($body);

        $this->parseSearchResultsPage($page, (string) $url);

        usleep(BusinessParser::randomDelayMs() * 1000);
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
        $name = mb_substr((string) ($data['name'] ?? ''), 0, 191);
        $address = mb_substr((string) ($data['address'] ?? ''), 0, 191);

        if (empty($name)) {
            return;
        }

        $hash = Business::generateDedupHash($name, $address);

        $business = Business::where('dedup_hash', $hash)->first();

        $updateData = [
            'scraping_job_id' => $this->scrapingJob->id,
            'name' => $name,
            'category' => $data['category'] ?? ($business->category ?? null),
            'address' => $address ?: ($business->address ?? null),
            'city' => $data['city'] ?: $this->scrapingJob->location,
            'state' => $data['state'] ?: ($business->state ?? null),
            'zip' => $data['zip'] ?: ($business->zip ?? null),
            'country' => $data['country'] ?? ($business->country ?? $this->deriveCountryFromLocation()),
            'phone' => $data['phone'] ?: ($business->phone ?? null),
            'website' => $data['website'] ?: ($business->website ?? null),
            'rating' => $data['rating'] ?? ($business->rating ?? null),
            'reviews_count' => $data['reviews_count'] ?? ($business->reviews_count ?? null),
            'source' => $this->scrapingJob->source,
            'dedup_hash' => $hash,
        ];

        // Final cleanup: if phone is in address, strip it
        $phoneRegex = '/(?:\+?\d{1,4}[\s.-]?)?(?:\(?\d{1,5}\)?[\s.-]?)?\d{2,4}[\s.-]?\d{3,4}[\s.-]?\d{3,4}/';

        // 1. If we have a phone, try to strip it directly
        if (! empty($updateData['phone']) && ! empty($updateData['address'])) {
            $phone = $updateData['phone'];
            if (str_contains($updateData['address'], $phone)) {
                $updateData['address'] = trim(str_replace($phone, '', $updateData['address']), " \t\n\r\0\x0B,-·");
            }
        }

        // 2. Also run regex on address to catch any other embedded phones
        if (! empty($updateData['address'])) {
            if (preg_match($phoneRegex, $updateData['address'], $matches)) {
                $foundPhone = $matches[0];
                if (empty($updateData['phone'])) {
                    $updateData['phone'] = trim($foundPhone);
                }
                $updateData['address'] = trim(str_replace($foundPhone, '', $updateData['address']), " \t\n\r\0\x0B,-·");
            }
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
        $location = $this->scrapingJob->location;

        if (empty($location)) {
            return null;
        }

        // Common mapping for known locations
        $mappings = [
            'Dubai' => 'United Arab Emirates',
            'UAE' => 'United Arab Emirates',
            'India' => 'India',
            'USA' => 'United States',
            'UK' => 'United Kingdom',
            'London' => 'United Kingdom',
            'Tokyo' => 'Japan',
            'Japan' => 'Japan',
        ];

        foreach ($mappings as $key => $country) {
            if (str_contains(strtolower($location), strtolower($key))) {
                return $country;
            }
        }

        // If location contains a comma, the last part might be the country
        $parts = array_map('trim', explode(',', $location));
        if (count($parts) > 1) {
            return end($parts);
        }

        return null;
    }
}
