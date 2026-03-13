<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\DomCrawler\Crawler;

$url = 'https://www.dubaibusinessdirectory.com/search?q=plumber';

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ],
]);

try {
    echo "Testing Dubai Business Directory: $url\n";
    $response = $client->get($url);
    echo 'Status: '.$response->getStatusCode()."\n";
    echo 'Length: '.strlen($response->getBody())."\n";
    $crawler = new Crawler($response->getBody());
    echo 'Title: '.($crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : 'N/A')."\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
