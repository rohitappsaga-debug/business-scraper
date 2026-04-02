<?php

namespace App\Scrapers\Spiders;

use App\Models\Business;
use App\Models\BusinessEmail;
use App\Models\SocialLink;
use App\Scrapers\Parsers\EmailParser;
use App\Scrapers\Parsers\PhoneParser;
use Illuminate\Support\Facades\Log;
use RoachPHP\Downloader\Middleware\HttpErrorMiddleware;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;

class BusinessEnrichmentSpider extends BasicSpider
{
    public array $spiderOptions = [
        'request_delay' => 1000,
        'concurrency' => 2,
    ];

    public array $downloaderMiddleware = [
        HttpErrorMiddleware::class,
    ];

    /**
     * @return Request[]
     */
    protected function initialRequests(): array
    {
        $url = $this->context['website'];
        if (! str_starts_with($url, 'http')) {
            $url = 'https://'.$url;
        }

        return [
            new Request('GET', $url, [$this, 'parse']),
        ];
    }

    /**
     * @return \Generator<ParseResult>
     */
    public function parse(Response $response): \Generator
    {
        $businessId = $this->context['business_id'];
        $business = Business::find($businessId);

        if (! $business) {
            yield from [];

            return;
        }

        $html = $response->getBody();

        // 1. Extract Emails
        $emails = EmailParser::extractFromHtml($html);
        foreach ($emails as $email) {
            BusinessEmail::firstOrCreate(
                ['business_id' => $business->id, 'email' => $email],
                ['verified' => false]
            );
        }
        if (empty($business->email) && ! empty($emails)) {
            $business->update(['email' => $emails[0]]);
        }

        // 1.5 Extract Phone
        $phones = PhoneParser::extractFromHtml($html);
        if (empty($business->phone) && ! empty($phones)) {
            $business->update(['phone' => $phones[0]]);
            Log::info("Extracted phone from homepage for {$business->name}: {$phones[0]}");
        }

        // 2. Extract Social Links
        $this->extractSocialLinks($response, $business);

        // 3. Discover and Follow Contact/About pages
        $links = $this->discoverContactLinks($response);
        foreach ($links as $link) {
            yield $this->request('GET', $link, 'parseContactPage');
        }

        Log::info("Enriched business homepage: {$business->name}", ['id' => $business->id, 'emails' => count($emails)]);

        yield from []; // Yield nothing else from here
    }

    public function parseContactPage(Response $response): \Generator
    {
        $businessId = $this->context['business_id'];
        $business = Business::find($businessId);
        if (! $business) {
            yield from [];

            return;
        }

        $html = $response->getBody();
        $emails = EmailParser::extractFromHtml($html);
        foreach ($emails as $email) {
            BusinessEmail::firstOrCreate(
                ['business_id' => $business->id, 'email' => $email],
                ['verified' => false]
            );
        }

        $phones = PhoneParser::extractFromHtml($html);
        if (empty($business->phone) && ! empty($phones)) {
            $business->update(['phone' => $phones[0]]);
        }

        $this->extractSocialLinks($response, $business);

        Log::info("Enriched contact page: {$business->name}", ['emails' => count($emails)]);

        yield from [];
    }

    private function extractSocialLinks(Response $response, Business $business): void
    {
        $platforms = [
            'facebook' => '/facebook\.com/i',
            'instagram' => '/instagram\.com/i',
            'linkedin' => '/linkedin\.com/i',
            'twitter' => '/(?:twitter\.com|x\.com)/i',
            'youtube' => '/youtube\.com/i',
        ];

        $response->filter('a')->each(function (Crawler $node) use ($platforms, $business) {
            $href = $node->attr('href');
            if (! $href) {
                return;
            }

            foreach ($platforms as $platform => $regex) {
                if (preg_match($regex, $href)) {
                    SocialLink::updateOrCreate(
                        ['business_id' => $business->id, 'platform' => $platform],
                        ['url' => mb_substr($href, 0, 2048), 'is_active' => true]
                    );
                    break;
                }
            }
        });
    }

    private function discoverContactLinks(Response $response): array
    {
        $links = [];
        $keywords = ['contact', 'about', 'reach', 'support'];

        $response->filter('a')->each(function (Crawler $node) use (&$links, $keywords) {
            $text = strtolower($node->text(''));
            $href = $node->attr('href');

            if (! $href || str_starts_with($href, '#') || str_starts_with($href, 'mailto:')) {
                return;
            }

            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword) || str_contains(strtolower($href), $keyword)) {
                    try {
                        $links[] = $node->getUri();
                    } catch (\Exception $e) {
                    }
                    break;
                }
            }
        });

        return array_values(array_unique(array_slice($links, 0, 5)));
    }
}
