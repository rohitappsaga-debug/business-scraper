


<?php

use App\Models\ScrapingJob;
use App\Jobs\ScrapeBusinessesJob;
use Illuminate\Support\Facades\Log;

// 1. Create a job manually
$job = ScrapingJob::create([
    'keyword' => 'dentist',
    'location' => 'surat',
    'status' => 'pending',
    'limit' => 5,
    'source' => 'Manual_Scratch_Test'
]);

echo "Created Job #{$job->id}\n";
Log::info("Manual Scratch Test: Created Job #{$job->id}");

// 2. Dispatch it
ScrapeBusinessesJob::dispatch($job);
echo "Dispatched Job to Queue. Please ensure 'php artisan queue:work' is running!\n";
Log::info("Manual Scratch Test: Dispatched Job #{$job->id}");
