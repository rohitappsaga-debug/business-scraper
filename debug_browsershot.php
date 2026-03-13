<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

$url = 'https://www.yellowpages.com/search?search_terms=Pizza&geo_location_terms=New+York';
echo 'DEBUG: Trying Browsershot for URL: '.$url."\n";

try {
    $html = Browsershot::url($url)
        ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
        ->noSandbox()
        ->bodyHtml();

    echo 'DEBUG: Captured HTML length: '.strlen($html)."\n";

    $crawler = new Crawler($html);
    $selectors = ['.v-card', '.result', '.info'];
    foreach ($selectors as $selector) {
        echo "DEBUG: Selector '$selector' count: ".$crawler->filter($selector)->count()."\n";
    }

    if ($crawler->filter('title')->count() > 0) {
        echo 'DEBUG: Page Title: '.$crawler->filter('title')->text()."\n";
    }

} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
