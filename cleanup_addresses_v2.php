<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Business;
use Illuminate\Contracts\Console\Kernel;

$businesses = Business::where('address', 'like', '%+%')->get();
$count = 0;

foreach ($businesses as $b) {
    $address = $b->address;
    // Look for anything that looks like a phone number at the end
    // Typically after a middle dot or just at the end with a plus
    if (preg_match('/\+[\d\s\-\.\(\)]{8,}$/', $address, $matches)) {
        $foundPhone = trim($matches[0]);
        echo "Found Phone: '$foundPhone' in ID: {$b->id}\n";

        if (empty($b->phone)) {
            $b->phone = $foundPhone;
        }

        // Remove from address
        $b->address = trim(str_replace($foundPhone, '', $b->address));
        // Clean up separators
        $b->address = preg_replace('/[ \t\n\r\0\x0B,·-]+$/u', '', $b->address);

        $b->save();
        $count++;
    }
}
echo "Cleaned $count records.\n";
