<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Business;
use App\Models\ScrapingJob;
use App\Scrapers\Crawlers\GoogleMapsCrawler;
use Illuminate\Contracts\Console\Kernel;

// Create a dummy scraping job
$job = ScrapingJob::create([
    'keyword' => 'dentist',
    'location' => 'Dubai',
    'source' => 'google_maps',
    'status' => 'running',
]);

// Cleanup: Delete any previous businesses for this keyword/location combo to ensure fresh data
Business::where('scraping_job_id', '>', 50)->delete();

echo "Created test job ID: {$job->id}\n";

try {
    echo "Starting crawl...\n";
    $crawler = GoogleMapsCrawler::crawl($job);
    echo 'Crawl finished. Saved count: '.$crawler->getSavedCount()."\n";

    // Check businesses saved
    $businesses = Business::where('scraping_job_id', $job->id)->get();
    foreach ($businesses as $b) {
        echo "Found: {$b->name} | Phone: ".($b->phone ?? 'NULL').' | Website: '.($b->website ?? 'NULL').' | CID: '.($b->cid ?? 'NULL')."\n";
    }
} catch (Exception $e) {
    echo 'Crawl failed: '.$e->getMessage()."\n";
}
