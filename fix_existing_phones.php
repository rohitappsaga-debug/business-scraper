<?php

use App\Models\Business;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$businesses = Business::whereNull('phone')
    ->orWhere('phone', '')
    ->get();

$count = 0;
foreach ($businesses as $business) {
    $address = $business->address;

    // Improved regex from BusinessParser
    $phoneRegex = '/(?:\+?\d{1,3}[\s.-]?)?\(?\d{2,5}\)?[\s.-]?\d{3,4}[\s.-]?\d{3,4}/';

    if (preg_match($phoneRegex, $address, $matches)) {
        $phone = trim($matches[0]);
        $newAddress = trim(str_replace($matches[0], '', $address), " \t\n\r\0\x0B,-");

        echo "Updating ID {$business->id}: Found phone [$phone] in address [$address]\n";

        $business->update([
            'phone' => $phone,
            'address' => $newAddress,
        ]);
        $count++;
    }
}

echo "\nFinished. Fixed $count records.\n";
