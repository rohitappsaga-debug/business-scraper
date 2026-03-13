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
                $href = $websiteLink->attr('href');
                // YP wraps external URLs — extract the real URL
                if (str_contains($href, 'redirect')) {
                    parse_str(parse_url($href, PHP_URL_QUERY), $params);
                    $website = $params['url'] ?? $params['website'] ?? $href;
                } else {
                    $website = $href;
                }
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
        // Google often stores name in div[role="heading"] or within specific data attributes
        $name = self::extractText($node, [
            '.dbg0pd',           // Typical name selector in local pack
            '[role="heading"]',
            'div[class*="title"]',
            '.fullName',
        ]);

        $rating = null;
        try {
            $ratingEl = $node->filter('.yi40Hd, [aria-label*="Rated"]');
            if ($ratingEl->count() > 0) {
                // Try text first
                $text = $ratingEl->text('');
                if (preg_match('/(\d+(?:\.\d+)?)/', $text, $m)) {
                    $rating = (float) $m[1];
                } elseif ($ratingEl->count() > 0 && ($attr = $ratingEl->attr('aria-label'))) {
                    // Fallback to aria-label
                    if (preg_match('/(\d+(?:\.\d+)?)/', $attr, $m)) {
                        $rating = (float) $m[1];
                    }
                }
            }
        } catch (\Exception) {
        }

        $reviews = null;
        try {
            $reviewsEl = $node->filter('.RDApEe, [aria-label*="reviews"]');
            if ($reviewsEl->count() > 0) {
                $text = $reviewsEl->text('');
                if (preg_match('/([\d,]+)/', $text, $m)) {
                    $reviews = (int) str_replace(',', '', $m[1]);
                }
            }
        } catch (\Exception) {
        }

        // Address is often in the details section
        $rawAddress = self::extractText($node, [
            '.rllt__details > div:nth-child(3)',
            '.rllt__details > div:nth-child(2)',
            '.rllt__details div',
            'div[class*="address"]',
        ]);

        $phone = self::extractText($node, [
            '.rllt__details div span[dir="ltr"]',
            'span[dir="ltr"]',
            'span[class*="phone"]',
            'div[class*="phone"]',
        ]);

        // Post-process Google's concatenated address string
        $address = '';
        $detectedCountry = null;
        if (! empty($rawAddress)) {
            // Split by middle dot dot (·) — Google uses this to separate fields
            $parts = array_map('trim', explode('·', $rawAddress));
            $cleanAddressParts = [];

            foreach ($parts as $part) {
                // 1. Check if it's a phone number (and we don't have one yet)
                if (empty($phone) && preg_match('/(\+\d{1,3}\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $part)) {
                    $phone = $part;

                    continue;
                }

                // 2. Check if it's "years in business"
                if (preg_match('/years in business/i', $part)) {
                    continue;
                }

                // 3. Check if it's a rating like "4.5(123)"
                if (preg_match('/^\d\.\d\(\d+\)$/', $part)) {
                    continue;
                }

                // 4. Check for country (either at end of part with comma or exact match)
                if (preg_match('/(?:, )?(US|United States|India|United Arab Emirates|UAE)$/i', $part, $m)) {
                    $detectedCountry = trim($m[1]);
                    // Remove country and any preceding comma/space
                    $part = preg_replace('/(?:, )?(US|United States|India|United Arab Emirates|UAE)$/i', '', $part);
                    // Trim trailing separators like dashes or commas that might be left over
                    $part = trim($part, " \t\n\r\0\x0B,-");

                    if (empty($part)) {
                        continue;
                    }
                }

                $cleanAddressParts[] = $part;
            }

            $address = implode(', ', $cleanAddressParts);
        }

        // Website extraction
        $website = '';
        try {
            // First try the specific "Website" button link
            $websiteEl = $node->filter('a.L48Cpd, a[aria-label*="Website"], a[href*="http"]:not([href*="google.com"])');
            if ($websiteEl->count() > 0) {
                $href = $websiteEl->attr('href');
                if ($href && ! str_contains($href, 'google.com')) {
                    $website = $href;
                }
            }

            // Fallback: search all links
            if (empty($website)) {
                $links = $node->filter('a[href*="http"]');
                $links->each(function (Crawler $link) use (&$website) {
                    if (! empty($website)) {
                        return;
                    }
                    $href = $link->attr('href');
                    if ($href && ! str_contains($href, 'google.com') && ! str_contains($href, 'maps.google')) {
                        $website = $href;
                    }
                });
            }
        } catch (\Exception) {
        }

        return [
            'name' => $name,
            'category' => 'Local Business',
            'address' => $address,
            'city' => '', // Will be filled by observer if needed
            'state' => '',
            'country' => $detectedCountry,
            'phone' => $phone,
            'website' => $website,
            'rating' => $rating,
            'reviews_count' => $reviews,
        ];
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
