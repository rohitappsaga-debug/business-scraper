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
