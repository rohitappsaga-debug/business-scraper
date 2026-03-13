<?php

namespace App\Scrapers\Observers;

use App\Jobs\EnrichBusinessJob;
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
        if (str_contains($url, 'duckduckgo.com')) {
            $this->parseDuckDuckGoResults($page);

            return;
        }

        if (str_contains($url, 'google.com/search') || $this->scrapingJob->source === 'google_maps') {
            $this->parseGoogleResults($page);

            return;
        }

        // YellowPages search result cards
        $selectors = [
            '.v-card',
            'div[class*="v-card"]',
            '.result .info',
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
     * Parse Google Local Search results.
     */
    private function parseGoogleResults(Crawler $page): void
    {
        // Try to find the local results container - prioritize high-level containers that include website buttons
        $selectors = [
            '.Vkp96c',
            'div[data-cid]',
            '.tF2Cxc',
            'div[jscontroller="At6S7b"]',
            '.rllt__details', // Fallback to just text details if parent isn't found
        ];

        foreach ($selectors as $selector) {
            $nodes = $page->filter($selector);
            if ($nodes->count() > 0) {
                $nodes->each(function (Crawler $node) {
                    $this->processGoogleResult($node);
                });

                return;
            }
        }
    }

    private function processGoogleResult(Crawler $node): void
    {
        try {
            $data = BusinessParser::parseGoogleMapsResult($node);

            if (empty($data['name'])) {
                return;
            }

            $this->saveBusiness($data);
        } catch (Exception $e) {
            Log::debug('Failed to parse Google result', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Parse DuckDuckGo HTML search results.
     */
    private function parseDuckDuckGoResults(Crawler $page): void
    {
        $page->filter('.result__body')->each(function (Crawler $result) {
            try {
                $titleEl = $result->filter('.result__title .result__a');
                if ($titleEl->count() === 0) {
                    return;
                }

                $name = trim($titleEl->text(''));
                $website = BusinessParser::cleanWebsiteUrl($titleEl->attr('href'), 'https://duckduckgo.com');
                $snippet = $result->filter('.result__snippet')->count() > 0
                    ? trim($result->filter('.result__snippet')->text(''))
                    : '';

                if (empty($name)) {
                    return;
                }

                $this->saveBusiness([
                    'name' => $name,
                    'website' => $website,
                    'category' => 'Search Result',
                    'address' => $snippet, // Snippet often contains location info
                    'city' => $this->scrapingJob->location,
                    'phone' => '',
                ]);
            } catch (Exception $e) {
                Log::debug('Failed to parse DDG result', ['error' => $e->getMessage()]);
            }
        });
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

        $business = Business::firstOrCreate(
            ['dedup_hash' => $hash],
            [
                'scraping_job_id' => $this->scrapingJob->id,
                'name' => $name,
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'address' => $address ?: null,
                'city' => $data['city'] ?? $this->scrapingJob->location,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? 'US',
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'rating' => $data['rating'] ?? null,
                'reviews_count' => $data['reviews_count'] ?? null,
                'opening_hours' => $data['opening_hours'] ?? null,
                'source' => $this->scrapingJob->source,
                'dedup_hash' => $hash,
            ]
        );

        if ($business->wasRecentlyCreated) {
            $this->savedCount++;

            // Trigger enrichment
            EnrichBusinessJob::dispatch($business)->onQueue('default');

            if (! empty($business->website)) {
                ExtractEmailsJob::dispatch($business)->onQueue('default');
            }
        }
    }
}
