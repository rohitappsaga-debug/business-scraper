<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Jobs\ScrapeBusinessesJob;
use App\Models\Business;
use App\Models\ScrapingJob;
use Illuminate\Contracts\Console\Kernel;

// Clear previous runs
Business::where('city', 'New York')->delete();

$job = ScrapingJob::create([
    'keyword' => 'Pizza',
    'location' => 'New York',
    'source' => 'yellowpages',
    'status' => 'pending',
]);

echo "DEBUG: Starting US Scrape job for NY...\n";

try {
    ScrapeBusinessesJob::dispatchSync($job);
    $job->refresh();
    echo 'DEBUG: Job Status: '.$job->status."\n";
    echo 'DEBUG: Results saved: '.$job->results_count."\n";
} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
