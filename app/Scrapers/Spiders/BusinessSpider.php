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
        $keyword = trim($this->context['keyword'] ?? 'Business');
        $location = trim($this->context['city'] ?? 'Surat');

        Log::info("BusinessSpider: Running Robust Multi-URL mode for '{$keyword}' in '{$location}'");

        $requests = [];
        $city = ucwords(strtolower(trim($location)));
        $citySlug = str_replace(' ', '-', $city);
        $keywordSlug = str_replace(' ', '-', strtolower($keyword));
        
        // 1. JustDial: Multiple Patterns (Singular & Plural)
        $jdPatterns = [
            "https://www.justdial.com/{$citySlug}/{$keywordSlug}",
            "https://www.justdial.com/{$citySlug}/{$keywordSlug}s",
            "https://www.justdial.com/{$citySlug}/{$keywordSlug}/area-{$citySlug}",
        ];

        foreach ($jdPatterns as $jdUrl) {
            $requests[] = new Request('GET', $jdUrl, [$this, 'parseJustdial'], [
                'headers' => array_merge($this->buildHeaders(), [
                    'Cookie' => "scity=" . urlencode($city) . "; city=" . urlencode($city),
                ]),
            ]);
        }

        // 2. Sulekha: Pluralized Keyword Strategy
        $sulekhaUrl = "https://www.sulekha.com/{$keywordSlug}s-in-{$citySlug}";
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
        $body = $response->getBody();
        $crawler = new Crawler($body);
        $targetCity = strtolower(trim($this->context['city'] ?? ''));
        
        // 1. Recursive JSON-LD Extraction
        $businesses = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$businesses) {
            $json = json_decode($node->text(), true);
            if (is_array($json)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($json));
                $temp = [];
                foreach ($iterator as $k => $v) {
                    $keys = [];
                    for ($i = 0; $i <= $iterator->getDepth(); $i++) { $keys[] = $iterator->getSubIterator($i)->key(); }
                    
                    // Look for 'name' keys and backtrack to find parent object
                    if ($k === 'name' && is_string($v) && strlen($v) > 2) {
                        $parent = $json;
                        foreach (array_slice($keys, 0, -1) as $step) { $parent = $parent[$step] ?? null; }
                        
                        if (isset($parent['@type']) && (stripos($parent['@type'], 'Business') !== false || stripos($parent['@type'], 'Restaurant') !== false || stripos($parent['@type'], 'Place') !== false)) {
                            $businesses[] = $parent;
                        }
                    }
                }
            }
        });

        $found = 0;
        
        // 2. Parse HTML Nodes (Extremely Broad)
        $nodes = $crawler->filter('.resultbox_info, .jsx-842217686, li[class*="listing"], .cntanr, .cnt_details, [data-id], .result-box, .store-details, section[class*="listing"], div[class*="listing"]');
        Log::info("JustDial: Found " . $count = $nodes->count() . " potential HTML nodes.");
        
        $yieldedFromLd = 0;
        if (!empty($businesses)) {
            // Deduplicate JSON-LD findings by name
            $uniqueBusinesses = [];
            foreach ($businesses as $b) { $uniqueBusinesses[$b['name']] = $b; }

            foreach ($uniqueBusinesses as $biz) {
                $name = $biz['name'];
                $locality = $biz['address']['addressLocality'] ?? '';
                $region = $biz['address']['addressRegion'] ?? '';
                $postalCode = $biz['address']['postalCode'] ?? '';
                $fullAddress = trim(($biz['address']['streetAddress'] ?? '') . ", " . $locality . ", " . $region . " " . $postalCode, ", ");
                
                // 1. Location rejection (Metro blocking)
                $isMetropoliton = false;
                $metros = ['mumbai', 'delhi', 'bangalore', 'chennai', 'kolkata', 'hyderabad', 'ahmedabad', 'pune', 'surat', 'jaipur', 'lucknow', 'kanpur', 'nagpur', 'indore', 'thane', 'bhopal', 'visakhapatnam', 'pimpri', 'patna', 'vadodara', 'ghaziabad', 'ludhiana', 'agra', 'nashik', 'faridabad', 'meerut', 'rajkot', 'kalyan', 'vasai', 'varanasi', 'srinagar', 'aurangabad', 'dhanbad', 'amritsar', 'navi-mumbai', 'allahabad', 'ranchi', 'howrah', 'jabalpur', 'gwalior', 'vijayawada', 'jodhpur', 'madurai', 'raipur', 'kota', 'chandigarh', 'guwahati', 'sholapur', 'hubli', 'bareilly'];
                
                foreach ($metros as $m) {
                    if (str_contains(strtolower($fullAddress), $m) && !str_contains(strtolower($targetCity), $m)) {
                        $isMetropoliton = true;
                        break;
                    }
                }
                if ($isMetropoliton) continue;

                if (! $this->isHighlyRelevant($fullAddress, $targetCity) && ! $this->isHighlyRelevant($region, $targetCity)) {
                    continue;
                }

                // Enrichment search in HTML
                $phone = null;
                $website = null;
                foreach ($nodes as $node) {
                    if (str_contains(strtolower($node->textContent), strtolower($name))) {
                        $nodeCrawler = new Crawler($node);
                        $phone = $this->extractPhone($nodeCrawler);
                        $website = $nodeCrawler->filter('a[href*="http"]')->each(function(Crawler $li) {
                            $href = $li->attr('href');
                            if (str_contains($href, 'justdial.com') || str_contains($href, 'facebook.com') || str_contains($href, 'twitter.com')) return null;
                            return $href;
                        });
                        $website = array_filter($website)[0] ?? null;
                        if ($phone || $website) break;
                    }
                }

                yield $this->item([
                    'name' => $name,
                    'phone' => $phone,
                    'website' => $website,
                    'address' => $fullAddress,
                    'city' => $this->context['city'],
                    'category' => $biz['@type'] ?? null,
                    'rating' => $biz['aggregateRating']['ratingValue'] ?? null,
                    'source' => 'justdial',
                    'scraping_job_id' => $this->context['job_id'] ?? null,
                ]);
                $found++;
                $yieldedFromLd++;
            }
        }

        // 3. Robust Hybrid Fallback
        if ($yieldedFromLd < 3) {
            foreach ($nodes as $node) {
                try {
                    $nodeCrawler = new Crawler($node);
                    $name = $this->extractCommonText($nodeCrawler, ['span[class*="title"]', '.lng_cont_name', '.resultbox_title_anchor', '.cont_list_title', 'h1', 'h2', 'h3']);
                    if (! $name || strlen($name) < 2 || stripos($name, 'Restaurants in') !== false) continue;

                    $address = $this->extractCommonText($nodeCrawler, ['.resultbox_address', '.cont_fl_addr', '.addr', '.cont_list_addr', '[class*="address"]', '.loc-text']);
                    
                    $isMetropoliton = false;
                    foreach ($metros ?? ['delhi', 'mumbai'] as $m) {
                        if (str_contains(strtolower($address), $m) && !str_contains(strtolower($targetCity), $m)) {
                            $isMetropoliton = true;
                            break;
                        }
                    }
                    if ($isMetropoliton || ! $this->isHighlyRelevant($address, $targetCity)) continue;

                    yield $this->item([
                        'name' => $name,
                        'phone' => $this->extractPhone($nodeCrawler),
                        'address' => $address ?: $targetCity,
                        'city' => $this->context['city'],
                        'source' => 'justdial-html',
                        'scraping_job_id' => $this->context['job_id'] ?? null,
                    ]);
                    $found++;
                } catch (\Exception) { continue; }
            }
        }

        Log::info("JustDial: yielded {$found} results for '{$targetCity}' using " . (empty($businesses) ? "HTML" : "JSON-LD") . " mode.");
    }

    public function parseSulekha(Response $response): \Generator
    {
        $crawler = new Crawler($response->getBody());
        $nodes = $crawler->filter('.listing-item, .list-box, li[class*="listing"], [active-id]');
        $targetCity = strtolower(trim($this->context['city'] ?? ''));
        $found = 0;

        Log::info("Sulekha: Processing " . $nodes->count() . " nodes for '{$targetCity}'");

        foreach ($nodes as $node) {
            try {
                $nodeCrawler = new Crawler($node);
                $name = $this->extractCommonText($nodeCrawler, ['h2', 'h3', '.name', '[class*="title"]', '.list-title']);
                if (! $name) {
                    continue;
                }

                $address = $this->extractCommonText($nodeCrawler, ['.address', '.location', '.loc-text']);
                if (! $this->isHighlyRelevant($address, $targetCity)) {
                    continue;
                }

                $phone = $this->extractPhone($nodeCrawler);
                
                yield $this->item([
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address ?: $this->context['city'],
                    'city' => $this->context['city'],
                    'source' => 'sulekha',
                    'scraping_job_id' => $this->context['job_id'] ?? null,
                ]);
                $found++;
            } catch (\Exception $e) { continue; }
        }

        Log::info("Sulekha: yielded {$found} results for '{$targetCity}'.");
    }

    private function isHighlyRelevant(?string $address, string $targetCity): bool
    {
        if (!$address) {
            return false; 
        }
        
        $addr = strtolower($address);
        $city = strtolower($targetCity);
        
        // 1. HARD REJECT: Irrelevant major metros (unless they are the target)
        $irrelevantMetros = ['mumbai', 'thane', 'navi', 'maharashtra', 'pune', 'bangalore'];
        foreach ($irrelevantMetros as $metro) {
            if (str_contains($addr, $metro) && !str_contains($city, $metro)) {
                return false;
            }
        }

        // 2. FUZZY MATCH: Target city or parent city
        // If searching "New Delhi", "Delhi" is a match.
        // If searching "Kamrej", "Surat" is a match.
        if (str_contains($addr, $city)) return true;
        
        // Split target city into words (e.g. "New Delhi" -> ["new", "delhi"])
        $words = explode(' ', $city);
        foreach ($words as $word) {
            if (strlen($word) > 3 && str_contains($addr, $word)) return true;
        }

        // Parent city fallback for Kamrej specifically
        if ($city === 'kamrej' && str_contains($addr, 'surat')) return true;

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
        // 1. Try attributes first (Most reliable for JustDial)
        // Check for tel links or data-phone/data-mob attributes
        $phone = $c->filter('a[href^="tel:"]')->attr('href');
        if ($phone) {
            $clean = preg_replace('/\D/', '', str_replace('tel:', '', $phone));
            if (strlen($clean) >= 10) return $clean;
        }

        $phone = $c->filter('[data-phone], [data-mob]')->attr('data-phone') ?: $c->filter('[data-phone], [data-mob]')->attr('data-mob');
        if ($phone) {
            $clean = preg_replace('/\D/', '', $phone);
            if (strlen($clean) >= 10) return $clean;
        }

        // 2. Fallback to common selectors and raw text
        foreach (['[class*="phone"]', '[class*="mobile"]', '.lng_contact', '.cont_fl_phone', '.callanr'] as $s) {
            if ($c->filter($s)->count() > 0) {
                $raw = preg_replace('/[^\d+\-\s()]/', '', $c->filter($s)->first()->text());
                $clean = preg_replace('/\D/', '', $raw);
                if (strlen($clean) >= 10) {
                    return $clean;
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
        $uas = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        ];

        return [
            'User-Agent' => $uas[array_rand($uas)],
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Referer' => 'https://www.google.com/',
        ];
    }
}
