<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessEmail;
use App\Scrapers\Parsers\BusinessParser;
use App\Scrapers\Parsers\EmailParser;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ExtractEmailsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public readonly Business $business) {}

    public function handle(): void
    {
        $website = $this->business->website;

        // Use BusinessParser to clean any lingering redirects
        // If it starts with / it's likely a Google relative URL
        $baseUrl = str_starts_with($website, '/') ? 'https://www.google.com' : null;
        $website = BusinessParser::cleanWebsiteUrl($website, $baseUrl);

        if (empty($website) || ! str_contains($website, '.')) {
            Log::info('Skipping email extraction: Invalid website', ['business_id' => $this->business->id, 'website' => $website]);

            return;
        }

        if (! str_starts_with($website, 'http')) {
            $website = 'https://'.$website;
        }

        try {
            $client = new Client([
                RequestOptions::HEADERS => [
                    'User-Agent' => BusinessParser::randomUserAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                ],
                RequestOptions::TIMEOUT => 20,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::VERIFY => false,
                RequestOptions::ALLOW_REDIRECTS => ['max' => 4],
            ]);

            $response = $client->get($website);
            $html = (string) $response->getBody();

            $emails = EmailParser::extractFromHtml($html);

            // Also check for contact links
            $contactLinks = $this->discoverContactLinks($html, $website);
            foreach ($contactLinks as $link) {
                try {
                    $subResponse = $client->get($link);
                    $subHtml = (string) $subResponse->getBody();
                    $subEmails = EmailParser::extractFromHtml($subHtml);
                    $emails = array_merge($emails, $subEmails);
                } catch (\Exception) {
                    continue;
                }
            }

            $emails = array_values(array_unique($emails));

            foreach ($emails as $email) {
                BusinessEmail::firstOrCreate(
                    [
                        'business_id' => $this->business->id,
                        'email' => $email,
                    ],
                    ['verified' => false]
                );
            }

            // Set the primary email on the business if none is set
            if (empty($this->business->email) && ! empty($emails)) {
                $this->business->update(['email' => $emails[0]]);
            }

            Log::info('Emails extracted', [
                'business_id' => $this->business->id,
                'found' => count($emails),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch website for email extraction', [
                'business_id' => $this->business->id,
                'website' => $website,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Discover contact/about links in HTML.
     *
     * @return list<string>
     */
    private function discoverContactLinks(string $html, string $baseUrl): array
    {
        $crawler = new Crawler($html, $baseUrl);
        $links = [];

        try {
            $crawler->filter('a')->each(function (Crawler $node) use (&$links) {
                $text = strtolower($node->text(''));
                $href = $node->attr('href');

                if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                    return;
                }

                $keywords = ['contact', 'about', 'reach', 'support', 'info'];
                foreach ($keywords as $keyword) {
                    if (str_contains($text, $keyword) || str_contains(strtolower($href), $keyword)) {
                        $links[] = $node->getUri();
                        break;
                    }
                }
            });
        } catch (\Exception) {
        }

        return array_values(array_unique(array_slice($links, 0, 3))); // Limit to 3 subpages
    }
}
