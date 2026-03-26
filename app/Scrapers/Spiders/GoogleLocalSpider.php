<?php

namespace App\Scrapers\Spiders;

use App\Scrapers\Pipelines\SaveBusinessPipeline;
use Illuminate\Support\Facades\Log;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Symfony\Component\DomCrawler\Crawler;

class GoogleLocalSpider extends BasicSpider
{
    public array $spiderOptions = [
        'request_delay' => 3000,
        'concurrency' => 1,
    ];

    public array $itemProcessors = [
        SaveBusinessPipeline::class,
    ];

    /**
     * @return Request[]
     */
    protected function initialRequests(): array
    {
        $keyword = trim($this->context['keyword'] ?? 'Business');
        $location = trim($this->context['city'] ?? 'Surat');

        // 100% Free Google Search (Local Tab)
        $query = urlencode("{$keyword} in {$location}");
        $url = "https://www.google.com/search?tbm=lcl&q={$query}&hl=en&gl=in";

        Log::info("GoogleLocalSpider: Scraping local results for '{$keyword}' in '{$location}'");

        return [
            new Request('GET', $url, [$this, 'parse'], [
                'headers' => $this->buildHeaders(),
            ]),
        ];
    }

    public function parse(Response $response): \Generator
    {
        $crawler = new Crawler($response->getBody());

        // Check for block/captcha
        if (str_contains($response->getBody(), 'unusual traffic') || str_contains($response->getBody(), 'CAPTCHA')) {
            Log::error('GoogleLocalSpider: BLOCKED by CAPTCHA');
            yield from [];

            return;
        }

        // Try multiple selectors for the result containers
        $selectors = ['.VkpGBb', '.rllt__wrap', 'div[data-cid]', '.C8ps9b', '.tF2Cxc'];
        $foundNodes = null;
        foreach ($selectors as $sel) {
            $foundNodes = $crawler->filter($sel);
            if ($foundNodes->count() > 0) {
                break;
            }
        }

        if (!$foundNodes || $foundNodes->count() === 0) {
            // Last ditch effort: find anything with a data-cid
            $foundNodes = $crawler->filter('[data-cid]');
        }

        if (!$foundNodes || $foundNodes->count() === 0) {
            Log::info('GoogleLocalSpider: No listings found with current selectors.');
            return;
        }

        Log::info('GoogleLocalSpider: Found '.$foundNodes->count().' potential listings.');

        $foundCount = 0;
        foreach ($foundNodes as $node) {
            try {
                $nodeCrawler = new Crawler($node);

                // 1. Name
                $nameSelectors = ['div[role="heading"]', '.OSrXXb', 'span.OSrXXb', '.dbg0pd', 'h3', '.V03Yec'];
                $name = $this->extractFirst($nodeCrawler, $nameSelectors);

                if (!$name || strlen($name) < 2) {
                    continue;
                }

                // 2. Rating & Reviews
                $rating = null;
                $reviewsCount = null;
                foreach (['.YAnN1d', '.MWpS9d', '.z3oM7d'] as $rSel) {
                    $ratingNode = $nodeCrawler->filter($rSel);
                    if ($ratingNode->count() > 0) {
                        $ratingText = $ratingNode->text();
                        if (preg_match('/(\d\.\d)/', $ratingText, $m)) $rating = $m[1];
                        if (preg_match('/\((\d+)\)/', $ratingText, $m)) $reviewsCount = $m[1];
                        break;
                    }
                }

                // 3. Address & Phone
                // Google often puts details in .rllt__details or similar
                $detailsText = $nodeCrawler->filter('.rllt__details')->text('');
                if (empty($detailsText)) {
                    $detailsText = $nodeCrawler->filter('.V03Yec')->text('');
                }
                if (empty($detailsText)) {
                    $detailsText = $nodeCrawler->text(); // Fallback to all text in container
                }

                // Try to extract phone specifically if it's visible separately
                $phone = null;
                if (preg_match('/(\+?\d[\d\s\-]{7,}\d)/', $detailsText, $m)) {
                    $phone = $m[1];
                }

                // 4. Website
                $website = $nodeCrawler->filter('a[href*="http"]')->each(function (Crawler $link) {
                    $href = $link->attr('href');
                    if (str_contains($href, 'google.com') || str_contains($href, 'maps.google') || str_contains($href, 'googleads')) {
                        return null;
                    }
                    return $href;
                });
                $website = array_filter($website)[0] ?? null;

                $cid = $nodeCrawler->attr('data-cid') ?: null;

                yield $this->item([
                    'name' => $name,
                    'address' => $detailsText,
                    'phone' => $phone,
                    'website' => $website,
                    'rating' => $rating,
                    'reviews_count' => $reviewsCount,
                    'cid' => $cid,
                    'city' => $this->context['city'],
                    'source' => 'google-local-free',
                    'scraping_job_id' => $this->context['job_id'] ?? null,
                ]);
                $foundCount++;

            } catch (\Exception $e) {
                Log::error('GoogleLocalSpider Error: '.$e->getMessage());
                continue;
            }
        }

        Log::info("GoogleLocalSpider: yielded {$foundCount} results.");
    }

    private function extractFirst(Crawler $c, array $selectors): ?string
    {
        foreach ($selectors as $s) {
            $node = $c->filter($s);
            if ($node->count() > 0) {
                return trim($node->first()->text());
            }
        }

        return null;
    }

    private function buildHeaders(): array
    {
        $uas = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ];

        return [
            'User-Agent' => $uas[array_rand($uas)],
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer' => 'https://www.google.com/',
        ];
    }
}
