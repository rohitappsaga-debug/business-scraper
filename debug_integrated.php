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
Business::where('city', 'Dubai')->delete();

$job = ScrapingJob::create([
    'keyword' => 'Plumber',
    'location' => 'Dubai',
    'source' => 'yellowpages', // Will be switched by factory to ddg
    'status' => 'pending',
]);

echo "DEBUG: Starting INTEGRATED Scrape job for Dubai (ID: {$job->id})...\n";

try {
    ScrapeBusinessesJob::dispatchSync($job);
    $job->refresh();
    echo 'DEBUG: Job Status: '.$job->status."\n";
    echo 'DEBUG: Results saved: '.$job->results_count."\n";

    $businesses = Business::where('scraping_job_id', $job->id)->get();
    echo 'DEBUG: Actual DB count: '.$businesses->count()."\n";
    foreach ($businesses as $b) {
        echo ' - '.$b->name.' ('.$b->website.")\n";
    }

} catch (Exception $e) {
    echo 'DEBUG ERROR: '.$e->getMessage()."\n";
}
