<?php

namespace App\Scrapers\Parsers;

use Symfony\Component\DomCrawler\Crawler;

class BusinessParser
{
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

    /**
     * Clean website URL by removing tracking parameters.
     */
    public static function cleanWebsiteUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);

        // Remove tracking parameters (anything after "?")
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        // Filter out Google Maps search URLs which are sometimes returned as websites
        if (str_contains($url, 'google.com/maps') || str_contains($url, 'google.co/maps')) {
            return null;
        }

        return $url;
    }
}
