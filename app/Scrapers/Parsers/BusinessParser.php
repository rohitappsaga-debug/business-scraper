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
            '.phone.primary',
            '.business-phone',
        ]);

        $website = '';
        try {
            $websiteLink = $card->filter('a.track-visit-website, a[href*="yellowpages.com/url"], a.website-link');
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
     * Parse a YellowPages business detail page.
     *
     * @return array<string, mixed>
     */
    public static function parseYellowPagesDetailPage(Crawler $page): array
    {
        $name = self::extractText($page, ['h1', '.dock-section h1', '.business-name']);
        $category = self::extractText($page, ['.categories a', '.categories']);

        $street = self::extractText($page, ['.street-address', '[itemprop="streetAddress"]']);
        $city = self::extractText($page, ['.locality', '[itemprop="addressLocality"]']);
        $state = self::extractText($page, ['.region', '[itemprop="addressRegion"]']);
        $zip = self::extractText($page, ['.zip', '[itemprop="postalCode"]']);

        $phone = self::extractText($page, [
            '.phone',
            '[itemprop="telephone"]',
            '.phone.primary',
            '.business-phone',
        ]);

        $website = null;
        try {
            $websiteLink = $page->filter('a.track-visit-website, a.website-link, .dock-section a[href*="http"]:not([href*="yellowpages.com"])');
            if ($websiteLink->count() > 0) {
                $href = $websiteLink->first()->attr('href');
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
            $ratingEl = $page->filter('.rating [class*="stars"], [class*="rating"]');
            if ($ratingEl->count() > 0) {
                if (preg_match('/(\d+(?:\.\d+)?)/', $ratingEl->attr('class') ?? '', $m)) {
                    $rating = (float) $m[1];
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
            'zip' => $zip,
            'phone' => $phone,
            'website' => $website,
            'rating' => $rating,
            'reviews_count' => null,
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
            'div.fontHeadlineSmall',
            'div.qBF1Pd',
            'div.jAN3S',
        ]);

        $rating = null;
        try {
            $ratingEl = $node->filter('.yi40Hd, [aria-label*="Rated"], span[aria-label*="stars"]');
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
            $reviewsEl = $node->filter('.RDApEe, [aria-label*="reviews"], span[aria-label*="reviews"]');
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
            '.W4Efsd:nth-child(2)',
            '.l3Y9cc',
        ]);

        $phone = self::extractPhone($node, [
            '.rllt__details div span[dir="ltr"]',
            'span[dir="ltr"]',
            'span[class*="phone"]',
            'div[class*="phone"]',
            '[aria-label*="Phone"]',
            '[data-tooltip*="phone"]',
            'span.Us79be',
            '.rllt__details div:last-child',
            '.rllt__details div div:last-child',
        ]);

        // Post-process Google's concatenated address string
        $cleaned = self::cleanGoogleAddress($rawAddress, $phone);

        // Latitude/Longitude extraction...

        // Website extraction
        $website = self::extractWebsite($node, [
            'a.L48Cpd',
            'a[aria-label*="Website"]',
            'a[href*="http"]:not([href*="google.com"])',
            'a[data-tooltip*="website"]',
            'a.m606Cc',
            'a[data-value="Website"]',
        ]);

        // Try to extract Lat/Lng if available in data attributes
        $latitude = null;
        $longitude = null;
        $cid = null;
        try {
            $latSelectors = ['[data-latitude]', '[data-lat]'];
            $lngSelectors = ['[data-longitude]', '[data-lng]'];

            foreach ($latSelectors as $sel) {
                $el = $node->filter($sel);
                if ($el->count() > 0) {
                    $latitude = (float) $el->attr(str_replace(['[', ']'], '', $sel));
                    break;
                }
            }
            foreach ($lngSelectors as $sel) {
                $el = $node->filter($sel);
                if ($el->count() > 0) {
                    $longitude = (float) $el->attr(str_replace(['[', ']'], '', $sel));
                    break;
                }
            }

            // Extract CID - very important for deep scraping
            $cid = $node->attr('data-cid') ?: $node->attr('data-ludocid') ?: $node->attr('data-id');
            if (empty($cid)) {
                // Try searching in links
                $cidLink = $node->filter('a[data-cid], a[href*="ludocid"], a[href*="cid="], a[data-id]');
                if ($cidLink->count() > 0) {
                    $cid = $cidLink->attr('data-cid') ?: $cidLink->attr('data-id');
                    if (empty($cid) && preg_match('/ludocid=([^&]+)/', $cidLink->attr('href'), $m)) {
                        $cid = $m[1];
                    }
                    if (empty($cid) && preg_match('/cid=([^&]+)/', $cidLink->attr('href'), $m)) {
                        $cid = $m[1];
                    }
                }
            }
            // If still empty, try parsing the whole node's text for a CID-like pattern if it's in a script/data
            if (empty($cid)) {
                $html = $node->html();
                if (preg_match('/0x[0-9a-f]+:0x[0-9a-f]+/', $html, $m)) {
                    $cid = $m[0];
                }
            }
        } catch (\Exception) {
        }

        return [
            'name' => $name,
            'category' => 'Local Business',
            'address' => $cleaned['address'],
            'city' => $cleaned['city'],
            'state' => $cleaned['state'],
            'zip' => $cleaned['zip'],
            'country' => $cleaned['country'],
            'phone' => $cleaned['phone'],
            'website' => $website,
            'rating' => $rating,
            'reviews_count' => $reviews,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'cid' => $cid,
        ];
    }

    /**
     * Parse a Google Maps detail page (CID or Search result detail).
     *
     * @return array<string, mixed>
     */
    public static function parseGoogleMapsDetailPage(Crawler $page): array
    {
        $name = self::extractText($page, ['h1', 'div[role="main"] h1', 'div.fontHeadlineLarge']);

        $phone = self::extractPhone($page, [
            '[data-tooltip*="phone"]',
            '[aria-label*="Phone"]',
            '[data-item-id*="phone"]',
            '[data-value*="Phone"]',
            'span[dir="ltr"]',
            '.Us79be',
            'div.Io6YTe',
            'button[data-tooltip*="phone"]',
        ]);

        $website = self::extractWebsite($page, [
            'a[data-tooltip*="website"]',
            'a[aria-label*="Website"]',
            'a.m606Cc',
            'a.L48Cpd',
            'a.ab_button',
            'a[href*="http"]:not([href*="google.com"])',
            'a[data-item-id="authority"]',
        ]);

        $address = self::extractText($page, [
            '[data-tooltip*="address"]',
            '[aria-label*="Address"]',
            '[data-item-id="address"]',
            'div.Io6YTe',
            'div.fontBodyMedium',
        ]);

        $cleaned = self::cleanGoogleAddress($address, $phone);

        return [
            'name' => $name,
            'address' => $cleaned['address'],
            'phone' => $cleaned['phone'],
            'website' => $website,
            'country' => $cleaned['country'],
            'city' => $cleaned['city'],
            'state' => $cleaned['state'],
        ];
    }

    /**
     * Centralized helper to clean Google Maps raw address strings.
     * Extracts and strips phone numbers, identifies country/city/state.
     *
     * @return array<string, mixed>
     */
    public static function cleanGoogleAddress(?string $rawAddress, ?string $existingPhone = null): array
    {
        $address = '';
        $phone = $existingPhone;
        $city = '';
        $state = '';
        $zip = '';
        $country = null;

        if (empty($rawAddress)) {
            return compact('address', 'phone', 'city', 'state', 'zip', 'country');
        }

        // 1. Normalize whitespace: replace newlines and multiple spaces with a single space
        $rawAddress = preg_replace('/\s+/', ' ', $rawAddress);

        // 2. Identify separator and split
        // Include Arabic comma (،) and middle dot (·)
        $separatorPattern = '/[·,،]|(?:\d\s+·\s+)/u';
        $parts = preg_split($separatorPattern, $rawAddress);
        $parts = array_map('trim', array_filter($parts));
        $cleanAddressParts = [];

        // 3. Flexible phone regex
        $phoneRegex = '/(?:\+?\d{1,4}[-.\s]?)?(?:\(?\d{1,5}\)?[-.\s]?)?\d{1,4}(?:[-.\s]?\d{1,4}){1,4}/u';

        foreach ($parts as $part) {
            // A. Check for phone number
            // We use a loop to strip ALL phone numbers from a single part
            $iterationCount = 0;
            while (preg_match($phoneRegex, $part, $matches) && $iterationCount < 5) {
                $iterationCount++;
                $foundPhone = trim($matches[0]);

                // Extract phone if missing or current is too short/invalid
                // Sanity check: must have at least 7 digits to be considered a phone
                if (preg_match_all('/\d/', $foundPhone) >= 7) {
                    if (empty($phone) || strlen(preg_replace('/\D/', '', $phone)) < 7) {
                        $phone = $foundPhone;
                    }

                    // Robust stripping: use the exact matched string to replace
                    $part = trim(str_replace($foundPhone, '', $part), " \t\n\r\0\x0B,-·،");
                }
            }

            if (empty($part)) {
                continue;
            }

            // B. Skip "years in business"
            if (preg_match('/years in business/i', $part)) {
                continue;
            }

            // C. Skip rating-like strings "4.5(120)"
            if (preg_match('/^\d\.\d\(\d+\)$/', $part)) {
                continue;
            }

            // D. Check for country
            $countryPatterns = [
                'US', 'United States', 'USA', 'India',
                'United Arab Emirates', 'UAE', 'Dubai',
                'UK', 'United Kingdom', 'Great Britain',
                'Canada', 'Australia', 'AU', 'Germany', 'Deutschland',
                'France', 'Japan', 'JP', 'China', 'Brazil', 'Brasil',
                'Singapore', 'Malaysia', 'Italy', 'Italia', 'Spain', 'España',
            ];
            $countryRegex = '/(?:, )?('.implode('|', array_map('preg_quote', $countryPatterns)).')$/i';

            if (preg_match($countryRegex, $part, $m)) {
                $country = trim($m[1]);
                $standardized = [
                    'UAE' => 'United Arab Emirates', 'Dubai' => 'United Arab Emirates',
                    'USA' => 'United States', 'US' => 'United States',
                    'UK' => 'United Kingdom', 'JP' => 'Japan', 'AU' => 'Australia',
                ];
                $country = $standardized[strtoupper($country)] ?? $country;

                $part = preg_replace($countryRegex, '', $part);
                $part = trim($part, " \t\n\r\0\x0B,-");

                if (empty($part)) {
                    continue;
                }
            }

            $cleanAddressParts[] = $part;
        }

        $address = implode(', ', $cleanAddressParts);

        // 4. Extract City, State, Zip from the end of the cleaned address
        if (preg_match('/(?:,\s+)?([^,]+),\s+([A-Z]{2}(?:\s+[A-Z]{2})?)\s+(\d{5}(?:-\d{4})?)$/i', $address, $m)) {
            $city = trim($m[1]);
            $state = trim($m[2]);
            $zip = trim($m[3]);
        } elseif (preg_match('/(?:,\s+)?([^,]+),\s+([A-Z]{2}(?:\s+[A-Z]{2})?)$/i', $address, $m)) {
            $city = trim($m[1]);
            $state = trim($m[2]);
        }

        return compact('address', 'phone', 'city', 'state', 'zip', 'country');
    }

    /**
     * Specialized phone extraction with regex validation.
     */
    private static function extractPhone(Crawler $node, array $selectors): ?string
    {
        // Require at least 7-8 digits to avoid matching short address segments or years
        $phoneRegex = '/(?:\+?\d{1,4}[-.\s]?)?(?:\(?\d{1,5}\)?[-.\s]?)?\d{1,4}(?:[-.\s]?\d{1,4}){1,4}/';

        foreach ($selectors as $selector) {
            try {
                $nodes = $node->filter($selector);
                foreach ($nodes as $el) {
                    $text = trim($el->textContent);

                    // Skip if it contains "services", "site", etc.
                    if (preg_match('/(?:services|site|open|closed|appointment|years)/i', $text)) {
                        continue;
                    }

                    if (preg_match($phoneRegex, $text, $matches)) {
                        $p = trim($matches[0]);
                        // Final sanity check: must have at least 7 digits
                        if (preg_match_all('/\d/', $p) >= 7) {
                            return $p;
                        }
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Specialized website extraction.
     */
    private static function extractWebsite(Crawler $node, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $el = $node->filter($selector);
                if ($el->count() > 0) {
                    $href = $el->first()->attr('href');
                    if ($href && ! str_contains($href, 'google.com') && ! str_contains($href, 'maps.google') && ! str_contains($href, 'retry/enablejs')) {
                        return $href;
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
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
    private static function extractText(Crawler $node, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $el = $node->filter($selector);
                if ($el->count() > 0) {
                    $text = trim($el->first()->text(''));
                    if ($text !== '') {
                        return $text;
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }
}
