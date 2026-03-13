<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

$url = 'https://duckduckgo.com/html/?q=dentist+texas';

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ]
]);

try {
    echo "Testing DuckDuckGo HTML: $url\n";
    $response = $client->get($url);
    echo "Status: " . $response->getStatusCode() . "\n";
    
    $crawler = new \Symfony\Component\DomCrawler\Crawler($response->getBody());
    $results = $crawler->filter('.result__title');
    echo "Results found: " . $results->count() . "\n";
    
    if ($results->count() > 0) {
         $results->slice(0, 5)->each(function ($n) {
             echo " - " . $n->text() . "\n";
         });
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
