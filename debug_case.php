<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScrapingJob;
use App\Scrapers\Crawlers\YellowPagesCrawler;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use App\Scrapers\Parsers\BusinessParser;

$job = ScrapingJob::create([
    'keyword' => 'Dentist',
    'location' => 'Texas',
    'source' => 'yellowpages',
    'status' => 'pending'
]);

$url = YellowPagesCrawler::buildSearchUrl($job);
echo "DEBUG URL: " . $url . "\n";

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => BusinessParser::randomUserAgent(),
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Referer' => 'https://www.yellowpages.com/',
    ]
]);

try {
    $response = $client->get($url);
    $html = (string) $response->getBody();
    echo "DEBUG: Status: " . $response->getStatusCode() . "\n";
    echo "DEBUG: HTML Length: " . strlen($html) . "\n";
    file_put_contents('yp_texas_debug.html', $html);
    
    $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
    
    // Check for "No Results" text
    if (str_contains(strtolower($html), 'no results')) {
        echo "DEBUG: 'no results' found in HTML text.\n";
    }

    // Check selectors
    $selectors = ['.v-card', '.result', '.info', 'div[class*="v-card"]', '.search-results'];
    foreach ($selectors as $selector) {
         echo "DEBUG: Selector '$selector' count: " . $crawler->filter($selector)->count() . "\n";
    }

} catch (\Exception $e) {
    echo "DEBUG ERROR: " . $e->getMessage() . "\n";
}
