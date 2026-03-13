<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\DomCrawler\Crawler;

// Google Local Search URL
$query = urlencode('dentist texas');
$url = "https://www.google.com/search?tbm=lcl&q={$query}";

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.9',
    ],
]);

try {
    echo "Testing Google Local Search: $url\n";
    $response = $client->get($url);
    echo 'Status: '.$response->getStatusCode()."\n";
    $html = (string) $response->getBody();
    echo 'Length: '.strlen($html)."\n";

    file_put_contents('google_debug.html', $html);

    $crawler = new Crawler($html);

    // Check for common local results containers
    $results = $crawler->filter('div.VkpSyc'); // Common container for local pack
    echo 'Results (VkpSyc) found: '.$results->count()."\n";

    // Try another selector
    $results2 = $crawler->filter('div[data-recordid]');
    echo 'Results (data-recordid) found: '.$results2->count()."\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
