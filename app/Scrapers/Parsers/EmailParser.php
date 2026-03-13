<?php

namespace App\Scrapers\Parsers;

class EmailParser
{
    private const EMAIL_REGEX = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    /**
     * Extract all unique email addresses from raw HTML content.
     *
     * @param  string  $html  Raw HTML string
     * @return list<string>
     */
    public static function extractFromHtml(string $html): array
    {
        // Strip script and style tags to reduce false positives
        $cleaned = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/si', '', $html) ?? $html;

        // Decode HTML entities
        $decoded = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match_all(self::EMAIL_REGEX, $decoded, $matches);

        if (empty($matches[0])) {
            return [];
        }

        return array_values(array_unique(
            array_filter(
                array_map('strtolower', $matches[0]),
                fn (string $email): bool => self::isValidEmail($email)
            )
        ));
    }

    /**
     * Validate an email address and filter out common false positives.
     */
    private static function isValidEmail(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $blocklist = [
            'example.com', 'test.com', 'domain.com', 'email.com',
            'yoursite.com', 'youremail.com', 'sentry.io',
        ];

        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        foreach ($blocklist as $blocked) {
            if (str_contains($domain, $blocked)) {
                return false;
            }
        }

        return true;
    }
}
