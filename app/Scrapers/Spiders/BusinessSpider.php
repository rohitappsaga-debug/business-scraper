<?php

namespace App\Scrapers\Spiders;

use App\Scrapers\Pipelines\SaveBusinessPipeline;
use Illuminate\Support\Facades\Log;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Symfony\Component\DomCrawler\Crawler;

class BusinessSpider extends BasicSpider
{
    /**
     * 100% FREE Area-Targeted Strategy:
     * We use specific directory URLs that force the area/city context,
     * bypassing automatic redirects to metropolitan cities.
     */
    public array $spiderOptions = [
        'request_delay' => 2000,
        'concurrency' => 2,
    ];

    public array $itemProcessors = [
        SaveBusinessPipeline::class,
    ];

    protected function initialRequests(): array
    {
        $keyword = $this->context['keyword'] ?? 'Business';
        $city = $this->context['city'] ?? 'Surat';

        Log::info("BusinessSpider: Running FREE Targeted mode for '{$keyword}' in '{$city}'");

        $requests = [];

        // 1. JustDial Area-Specific (Most reliable for Indian towns)
        // Format: justdial.com/City/Keyword/area-AreaName
        $citySlug = ucwords(strtolower(trim($city)));
        $areaSlug = 'area-'.ucwords(strtolower(trim($city))); // In our context, city often is the area (Kamrej)
        $jdUrl = "https://www.justdial.com/Surat/{$keyword}/{$areaSlug}";

        $requests[] = new Request('GET', $jdUrl, [$this, 'parseJustdial'], [
            'headers' => $this->buildHeaders(),
        ]);

        // 2. Sulekha Targeted
        $sulekhaUrl = 'https://www.sulekha.com/'.strtolower(str_replace(' ', '-', $keyword)).'/'.strtolower(str_replace(' ', '-', $city)).'-surat';
        $requests[] = new Request('GET', $sulekhaUrl, [$this, 'parseSulekha'], [
            'headers' => $this->buildHeaders(),
        ]);

        return $requests;
    }

    public function parse(Response $response): \Generator
    {
        yield from []; // Required by BasicSpider
    }

    public function parseJustdial(Response $response): \Generator
    {
        $crawler = new Crawler($response->getBody());
        $nodes = $crawler->filter('.resultbox_info, .jsx-842217686, li[class*="listing"], .cntanr');
        $targetCity = strtolower(trim($this->context['city'] ?? ''));
        $found = 0;

        foreach ($nodes as $node) {
            try {
                $nodeCrawler = new Crawler($node);
                $name = $this->extractCommonText($nodeCrawler, ['span[class*="title"]', '.lng_cont_name', '.resultbox_title_anchor']);
                if (! $name || strlen($name) < 2) {
                    continue;
                }

                $address = $this->extractCommonText($nodeCrawler, ['.resultbox_address', '.cont_fl_addr', '.addr']);

                // Smart Relevance
                if (! $this->isHighlyRelevant($address, $targetCity)) {
                    continue;
                }

                $phone = $this->extractPhone($nodeCrawler);
                $rating = $this->extractRating($nodeCrawler);

                yield $this->item([
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address ?: $this->context['city'],
                    'city' => $this->context['city'],
                    'rating' => $rating,
                    'source' => 'justdial',
                    'scraping_job_id' => $this->context['job_id'] ?? null,
                ]);
                $found++;
            } catch (\Exception $e) {
                continue;
            }
        }
        Log::info("JustDial: yielded {$found} results for '{$targetCity}'.");
    }

    public function parseSulekha(Response $response): \Generator
    {
        $crawler = new Crawler($response->getBody());
        $nodes = $crawler->filter('.listing-item, .list-box, li[class*="listing"]');
        $targetCity = strtolower(trim($this->context['city'] ?? ''));
        $found = 0;

        foreach ($nodes as $node) {
            try {
                $nodeCrawler = new Crawler($node);
                $name = $this->extractCommonText($nodeCrawler, ['h2', 'h3', '.name', '[class*="title"]']);
                if (! $name) {
                    continue;
                }

                $address = $this->extractCommonText($nodeCrawler, ['.address', '.location']);
                if (! $this->isHighlyRelevant($address, $targetCity)) {
                    continue;
                }

                yield $this->item([
                    'name' => $name,
                    'address' => $address ?: $this->context['city'],
                    'city' => $this->context['city'],
                    'source' => 'sulekha',
                    'scraping_job_id' => $this->context['job_id'] ?? null,
                ]);
                $found++;
            } catch (\Exception $e) {
                continue;
            }
        }
        Log::info("Sulekha: yielded {$found} results for '{$targetCity}'.");
    }

    private function isHighlyRelevant(?string $address, string $targetCity): bool
    {
        // If JustDial doesn't find the address, we can't verify relevance.
        // For small towns, it's safer to drop the result than to risk a Mumbai redirect.
        if (!$address) {
            return false; 
        }
        
        $addr = strtolower($address);
        $city = strtolower($targetCity);
        
        // REJECT if it explicitly mentions major irrelevant metros
        if (str_contains($addr, 'mumbai') || str_contains($addr, 'thane') || str_contains($addr, 'navi') || str_contains($addr, 'maharashtra')) {
            return false;
        }

        // ACCEPT if it contains Surat OR the target city
        if (str_contains($addr, 'surat') || str_contains($addr, $city)) {
            return true;
        }

        return false;
    }

    private function extractCommonText(Crawler $c, array $selectors): ?string
    {
        foreach ($selectors as $s) {
            if ($c->filter($s)->count() > 0) {
                return trim(strip_tags($c->filter($s)->first()->text()));
            }
        }

        return null;
    }

    private function extractPhone(Crawler $c): ?string
    {
        foreach (['[class*="phone"]', '[class*="mobile"]', '.lng_contact', '.cont_fl_phone'] as $s) {
            if ($c->filter($s)->count() > 0) {
                $raw = preg_replace('/[^\d+\-\s()]/', '', $c->filter($s)->first()->text());
                $clean = preg_replace('/\D/', '', $raw);
                if (strlen($clean) >= 7) {
                    return trim($raw);
                }
            }
        }

        return null;
    }

    private function extractRating(Crawler $c): ?string
    {
        $node = $c->filter('.resultbox_totalrate, [class*="rating"], .cont_fl_rate');
        if ($node->count() > 0) {
            preg_match('/\d+\.?\d*/', $node->first()->text(), $m);

            return $m[0] ?? null;
        }

        return null;
    }

    private function buildHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        ];
    }
}
