<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\ScrapingJob;
use App\Scrapers\Crawlers\DuckDuckGoCrawler;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\DomCrawler\Crawler;

$job = ScrapingJob::create([
    'keyword' => 'Plumber',
    'location' => 'Dubai',
    'source' => 'duckduckgo',
    'status' => 'pending',
]);

$url = DuckDuckGoCrawler::buildSearchUrl($job);
echo 'DEBUG URL: '.$url."\n";

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ],
]);

try {
    $response = $client->get($url);
    $html = (string) $response->getBody();
    echo 'DEBUG: HTML Length: '.strlen($html)."\n";
    file_put_contents('ddg_debug.html', $html);

    $crawler = new Crawler($html);

    $selectors = ['.result__body', '.result', '.result__title', 'a.result__a'];
    foreach ($selectors as $selector) {
        echo "DEBUG: Selector '$selector' count: ".$crawler->filter($selector)->count()."\n";
    }

    // Sometimes DDG HTML is in <div id="links">
    if ($crawler->filter('#links')->count() > 0) {
        echo "DEBUG: #links container found.\n";
    }

} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
