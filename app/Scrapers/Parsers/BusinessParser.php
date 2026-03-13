<?php

namespace App\Scrapers\Parsers;

use Symfony\Component\DomCrawler\Crawler;

class BusinessParser
{
    /**
     * Parse a YellowPages search result card into business data.
     *
     * @return array<string, mixed>
     */
    public static function parseYellowPagesCard(Crawler $card): array
    {
        $name = self::extractText($card, [
            '.business-name span',
            '.business-name',
            'h2.n span',
            'a.business-name',
        ]);

        $category = self::extractText($card, [
            '.categories a',
            '.categories',
            '.result-heading-category',
        ]);

        $street = self::extractText($card, [
            '.street-address',
            '[itemprop="streetAddress"]',
            '.adr .street-address',
        ]);

        $city = self::extractText($card, [
            '.locality',
            '[itemprop="addressLocality"]',
        ]);

        $state = self::extractText($card, [
            '.region',
            '[itemprop="addressRegion"]',
        ]);

        $phone = self::extractText($card, [
            '[itemprop="telephone"]',
            '.phones.phone.primary',
            '.phone',
        ]);

        $website = '';
        try {
            $websiteLink = $card->filter('a.track-visit-website, a[href*="yellowpages.com/url"]');
            if ($websiteLink->count() > 0) {
                $website = self::cleanWebsiteUrl($websiteLink->attr('href'), 'https://www.yellowpages.com');
            }
        } catch (\Exception) {
        }

        $rating = null;
        try {
            $ratingEl = $card->filter('[class*="rating"]');
            if ($ratingEl->count() > 0) {
                $ratingClass = $ratingEl->attr('class') ?? '';
                if (preg_match('/(\d+(?:\.\d+)?)/', $ratingClass, $m)) {
                    $rating = (float) $m[1];
                }
            }
        } catch (\Exception) {
        }

        $reviewCount = null;
        try {
            $reviewEl = $card->filter('.count, .reviews-count, [class*="review"]');
            if ($reviewEl->count() > 0) {
                $text = $reviewEl->text('');
                if (preg_match('/(\d+)/', $text, $m)) {
                    $reviewCount = (int) $m[1];
                }
            }
        } catch (\Exception) {
        }

        return [
            'name' => $name,
            'category' => $category,
            'address' => $street,
            'city' => $city,
            'state' => $state,
            'phone' => $phone,
            'website' => $website,
            'rating' => $rating,
            'reviews_count' => $reviewCount,
        ];
    }

    /**
     * Parse a Google Local Search result node.
     *
     * @return array<string, mixed>
     */
    public static function parseGoogleMapsResult(Crawler $node): array
    {
        // Google Local Pack results often have a complex concatenated string in the first few children
        // Example: "Business Name 4.5(20) · Category · Address"

        $name = self::extractText($node, ['.dbg0pd', '[role="heading"]', 'div[class*="title"]']);
        $rating = null;
        $reviews = null;
        $category = 'Local Business';
        $rawAddress = '';
        $phone = '';

        // Try to find the details container
        $detailsNode = $node->filter('.rllt__details');
        if ($detailsNode->count() > 0) {
            $divs = $detailsNode->filter('div');

            // First div often contains the rating/category line
            if ($divs->count() > 0) {
                $line1 = trim($divs->eq(0)->text(''));

                // Extract rating and reviews if present: "4.5(20) · Category"
                if (preg_match('/(\d+(?:\.\d+)?)\s*\(([\d,]+)\)\s*·\s*(.*)/', $line1, $m)) {
                    $rating = (float) $m[1];
                    $reviews = (int) str_replace(',', '', $m[2]);
                    $category = trim($m[3]);
                } elseif (preg_match('/·\s*(.*)/', $line1, $m)) {
                    $category = trim($m[1]);
                }
            }

            // Other divs contain address, hours, and phone
            $divs->each(function (Crawler $div, $i) use (&$rawAddress, &$phone) {
                $text = trim($div->text(''));
                if ($i === 0) {
                    return;
                } // Already handled rating/category line

                // Detect phone number
                if (preg_match('/(\+\d{1,3}\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $text, $m)) {
                    $phone = $text;

                    return;
                }

                // Skip snippets like "Open · Closes 8 pm" or review snippets
                if (preg_match('/(Open|Closed) ·/i', $text) || str_contains($text, '"')) {
                    return;
                }

                // If it looks like an address (has street-like patterns or multiple parts)
                if (empty($rawAddress) && strlen($text) > 5) {
                    $rawAddress = $text;
                }
            });
        }

        // Secondary fallback for website - look for standard buttons or aria-labels
        $website = '';
        try {
            $websiteEl = $node->filter('a.L48Cpd, a[aria-label*="Website"], a.ab_button[href*="http"], a[href*="googleadservices.com"]');
            if ($websiteEl->count() > 0) {
                $website = self::cleanWebsiteUrl($websiteEl->attr('href'), 'https://www.google.com');
            }
        } catch (\Exception) {
        }

        // Separate extraction for description since it's usually in a different tab or hidden
        $description = self::extractText($node, ['.Y_7S0 .V67S5c', 'div[jsaction*="pane.desc"]']);

        // Final address cleaning
        $address = $rawAddress;
        $detectedCountry = null;
        if (! empty($address)) {
            // Remove country from end if found
            if (preg_match('/(?:, )?(US|United States|India|United Arab Emirates|UAE)$/i', $address, $m)) {
                $detectedCountry = trim($m[1]);
                $address = preg_replace('/(?:, )?(US|United States|India|United Arab Emirates|UAE)$/i', '', $address);
                $address = trim($address, " \t\n\r\0\x0B,-");
            }
        }

        return [
            'name' => $name ?: 'Unknown Business',
            'category' => $category,
            'description' => $description,
            'address' => $address,
            'city' => '', // Filled by observer/enricher
            'state' => '',
            'country' => $detectedCountry,
            'phone' => $phone,
            'website' => $website,
            'rating' => $rating,
            'reviews_count' => $reviews,
            'opening_hours' => [], // Usually needs a separate detail crawl
        ];
    }

    /**
     * Clean and resolve a website URL from various redirect wrappers.
     */
    public static function cleanWebsiteUrl(string $url, ?string $baseUrl = null): string
    {
        if (empty($url)) {
            return '';
        }

        // Handle protocol-relative URLs
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        // Resolve relative URLs if base URL is provided
        if ($baseUrl && ! str_starts_with($url, 'http')) {
            $url = rtrim($baseUrl, '/').'/'.ltrim($url, '/');
        }

        // Handle Google Redirects
        if (str_contains($url, 'google.com/url?q=')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $url = $query['q'] ?? $url;
        }

        // Handle Google Search Redirects (e.g. /url?url=)
        if (str_contains($url, '/url?url=')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $url = $query['url'] ?? $url;
        }

        // Handle Google Ads (aclk) - we often can't unwrap these easily, but we can try to get the adurl
        if (str_contains($url, '/aclk?')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            if (! empty($query['adurl'])) {
                $url = $query['adurl'];
            }
        }

        // Handle DuckDuckGo Redirects
        if (str_contains($url, 'duckduckgo.com/l/?uddg=')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $url = $query['uddg'] ?? $url;
        }

        // Remove any tracking params if it's a direct URL now
        if (str_contains($url, 'utm_')) {
            $parts = parse_url($url);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $query = array_filter($query, fn ($k) => ! str_starts_with($k, 'utm_'), ARRAY_FILTER_USE_KEY);
                $newQuery = http_build_query($query);
                $url = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '').($newQuery ? '?'.$newQuery : '');
            }
        }

        return $url;
    }

    /**
     * Generate a fallback description if none is available.
     */
    public static function generateFallbackDescription(string $name, string $category, string $address): string
    {
        $location = '';
        if (! empty($address)) {
            $parts = explode(',', $address);
            $location = trim(end($parts));
        }

        return "{$name} is a {$category} located in {$location} specializing in professional services and customer care.";
    }

    /**
     * Parse opening hours from a table.
     *
     * @return array<string, string>
     */
    private static function parseOpeningHours(Crawler $table): array
    {
        $hours = [];
        try {
            $table->filter('tr')->each(function (Crawler $tr) use (&$hours) {
                $cells = $tr->filter('td');
                if ($cells->count() >= 2) {
                    $day = strtolower(trim($cells->eq(0)->text('')));
                    $time = trim($cells->eq(1)->text(''));
                    if (! empty($day)) {
                        $hours[$day] = $time;
                    }
                }
            });
        } catch (\Exception) {
        }

        return $hours;
    }

    public static function randomUserAgent(): string
    {
        $agents = config('scraper.user_agents', [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        ]);

        return $agents[array_rand($agents)];
    }

    public static function randomDelayMs(): int
    {
        $min = config('scraper.delay_min_ms', 1500);
        $max = config('scraper.delay_max_ms', 4000);

        return rand((int) $min, (int) $max);
    }

    /**
     * Try a list of CSS selectors and return the first text found.
     *
     * @param  list<string>  $selectors
     */
    private static function extractText(Crawler $node, array $selectors): string
    {
        foreach ($selectors as $selector) {
            try {
                $el = $node->filter($selector);
                if ($el->count() > 0) {
                    return trim($el->first()->text(''));
                }
            } catch (\Exception) {
                continue;
            }
        }

        return '';
    }
}
