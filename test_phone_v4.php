<?php

require __DIR__.'/vendor/autoload.php';

use App\Scrapers\Parsers\BusinessParser;

function testCleaning($label, $raw, $existingPhone = null)
{
    echo "Testing: $label\n";
    echo 'Raw: '.str_replace("\n", '[\\n]', $raw)."\n";

    $cleaned = BusinessParser::cleanGoogleAddress($raw, $existingPhone);

    echo 'Cleaned Address: '.$cleaned['address']."\n";
    echo 'Extracted Phone: '.($cleaned['phone'] ?? 'N/A')."\n";
    echo 'Country:         '.($cleaned['country'] ?? 'N/A')."\n";
    echo "----------------------------------------\n";
}

// User's latest failure case with Arabic separator and multiple data points in one segment
testCleaning('Jumeirah Terrace Arabic', '1 10 A Street Jumeirah Terrace - 107 شارع الثاني من ديسمب، +971 4 379 9954, Dubai, United Arab Emirates');

// Case with multiple phones or partial matches
testCleaning('Multiple phones', 'Storefront 1, +971 4 111 2222, Second Phone: +971 4 333 4444');

// Multiline case
testCleaning('Building No. 52 Multiline', "Building No. 52,\n+971 4 279\n8200");
