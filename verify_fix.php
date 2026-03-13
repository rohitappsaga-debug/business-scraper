<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScrapingJob;
use App\Jobs\ScrapeBusinessesJob;
use App\Models\Business;

// Clear previous runs
Business::where('city', 'Texas')->delete();

$job = ScrapingJob::create([
    'keyword' => 'Dentist',
    'location' => 'Texas',
    'source' => 'yellowpages', // Will be ignored because Factory defaults to DDG
    'status' => 'pending'
]);

echo "DEBUG: Starting NEW Scrape job for Dentist in Texas (ID: {$job->id})...\n";

try {
    ScrapeBusinessesJob::dispatchSync($job);
    $job->refresh();
    echo "DEBUG: Job Status: " . $job->status . "\n";
    echo "DEBUG: Results saved: " . $job->results_count . "\n";
    
    $businesses = Business::where('scraping_job_id', $job->id)->get();
    echo "DEBUG: DB Result Count: " . $businesses->count() . "\n";
    if ($businesses->count() > 0) {
        echo " - First result: " . $businesses->first()->name . "\n";
    }

} catch (\Exception $e) {
    echo "DEBUG ERROR: " . $e->getMessage() . "\n";
}
