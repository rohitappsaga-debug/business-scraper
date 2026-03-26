<?php

namespace App\Scrapers\Parsers;

class PhoneParser
{
    /**
     * Extract phone numbers from HTML content.
     */
    public static function extractFromHtml(string $html): array
    {
        // Regex for various phone formats (international and US)
        $regex = '/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/';

        preg_match_all($regex, $html, $matches);

        $phones = array_unique($matches[0]);

        // Clean up: remove duplicates that are just substrings of others
        return array_values(array_filter($phones, function ($phone) {
            return strlen(preg_replace('/\D/', '', $phone)) >= 10;
        }));
    }
}
