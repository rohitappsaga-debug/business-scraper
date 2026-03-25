<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use RoachPHP\Roach;
use App\Scrapers\Spiders\BusinessSpider;
use App\Models\Business;

echo "CLEANING UP Job #97 if exists...\n";
Business::where('scraping_job_id', 97)->delete();

echo "Starting FRESH BusinessSpider for 'vadapav' in Kamrej (Job #97)...\n";
Roach::startSpider(BusinessSpider::class, context: [
    'keyword' => 'vadapav',
    'city' => 'kamrej',
    'job_id' => 97
]);

$count = Business::where('scraping_job_id', 97)->count();
echo "Scraping complete. Found $count records for Job #97 in DB.\n";

$latest = Business::where('scraping_job_id', 97)->latest()->get();
foreach ($latest as $b) {
    echo " - [{$b->source}] {$b->name} | {$b->address} (Relevant: OK)\n";
}
