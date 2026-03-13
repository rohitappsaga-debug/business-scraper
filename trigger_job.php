<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\Contracts\Console\Kernel;

$job = ScrapingJob::create([
    'keyword' => 'Hospitals',
    'location' => 'New York',
    'source' => 'google_maps',
    'status' => 'pending',
]);

ScrapeBusinessesJob::dispatch($job);
echo 'Started job with ID: '.$job->id."\n";
