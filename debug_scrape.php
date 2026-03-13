<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\DomCrawler\Crawler;

$url = 'https://www.yellowpages.com/search?search_terms=Pizza&geo_location_terms=New+York';

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Ch-Ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'none',
        'Sec-Fetch-User' => '?1',
        'DNT' => '1',
    ],
]);

try {
    echo "DEBUG: Requesting with advanced headers...\n";
    $response = $client->get($url);
    echo 'DEBUG: Response status: '.$response->getStatusCode()."\n";
    $html = (string) $response->getBody();
    echo 'DEBUG: Body length: '.strlen($html)."\n";

    $crawler = new Crawler($html);
    echo 'DEBUG: Title: '.($crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : 'N/A')."\n";
    echo 'DEBUG: Cards found: '.$crawler->filter('.v-card, .result, .info')->count()."\n";

} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
