<?php

use App\Jobs\ScrapeBusinessesJob;
use App\Models\ScrapingJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automated Scraping Scheduler (enable by uncommenting)
|--------------------------------------------------------------------------
| These daily schedules automatically create scraping jobs and dispatch them.
| Make sure a queue worker is running for these to execute.
*/

// Schedule::call(function () {
//     $job = ScrapingJob::create([
//         'keyword' => 'Dentists',
//         'location' => 'USA',
//         'source' => 'yelp',
//         'status' => 'pending',
//     ]);
//     ScrapeBusinessesJob::dispatch($job);
// })->daily()->name('scrape-dentists');

// Schedule::call(function () {
//     $job = ScrapingJob::create([
//         'keyword' => 'Restaurants',
//         'location' => 'USA',
//         'source' => 'yelp',
//         'status' => 'pending',
//     ]);
//     ScrapeBusinessesJob::dispatch($job);
// })->daily()->name('scrape-restaurants');

// Schedule::call(function () {
//     $job = ScrapingJob::create([
//         'keyword' => 'Lawyers',
//         'location' => 'USA',
//         'source' => 'yelp',
//         'status' => 'pending',
//     ]);
//     ScrapeBusinessesJob::dispatch($job);
// })->daily()->name('scrape-lawyers');
