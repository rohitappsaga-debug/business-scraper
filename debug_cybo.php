<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\DomCrawler\Crawler;

$url = 'https://www.cybo.com/search/?q=plumber&where=dubai';

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ],
]);

try {
    echo 'DEBUG: Requesting Cybo: '.$url."\n";
    $response = $client->get($url);
    echo 'DEBUG: Status: '.$response->getStatusCode()."\n";
    $html = (string) $response->getBody();
    echo 'DEBUG: Body length: '.strlen($html)."\n";

    $crawler = new Crawler($html);
    echo 'DEBUG: Title: '.($crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : 'N/A')."\n";

    // Check for common result patterns on Cybo
    $selectors = ['.company', '.result', 'div[class*="company"]', 'div[class*="result"]'];
    foreach ($selectors as $selector) {
        echo "DEBUG: Selector '$selector' count: ".$crawler->filter($selector)->count()."\n";
    }

} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
