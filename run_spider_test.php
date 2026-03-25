<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Business;
use App\Scrapers\Spiders\BusinessSpider;
use Illuminate\Contracts\Console\Kernel;
use RoachPHP\Roach;

echo "Clearing previous records for Job #95...\n";
Business::where('scraping_job_id', 95)->delete();

echo "Starting BusinessSpider for Kamrej...\n";
Roach::startSpider(BusinessSpider::class, context: [
    'keyword' => 'restaurant',
    'city' => 'kamrej',
    'job_id' => 95,
]);

$count = Business::where('scraping_job_id', 95)->count();
echo "Scraping complete. Found $count records for Job #95 in DB.\n";

$latest = Business::where('scraping_job_id', 95)->latest()->take(5)->get();
foreach ($latest as $b) {
    echo " - [{$b->source}] {$b->name} | {$b->address}\n";
}
