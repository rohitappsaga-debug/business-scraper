<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Console\Kernel;

$sites = [
    'https://www.yellowpages.ae/search?q=plumber&location=dubai',
    'https://www.yellowpagesae.com/search?keyword=plumber&city=dubai',
    'https://www.yellowpages-uae.com/search.php?q=plumber&where=dubai',
];

$client = new Client([
    RequestOptions::VERIFY => false,
    RequestOptions::TIMEOUT => 10,
    RequestOptions::HEADERS => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ],
]);

foreach ($sites as $url) {
    echo "Testing URL: $url\n";
    try {
        $response = $client->get($url);
        echo 'Status: '.$response->getStatusCode()."\n";
        echo 'Length: '.strlen($response->getBody())."\n";
        if (stripos($response->getBody(), 'access denied') !== false) {
            echo "Result: Access Denied in body\n";
        } else {
            echo "Result: Look promising!\n";
        }
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "-------------------\n";
}
