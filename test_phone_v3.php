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

// User's latest failure case (from FULL ADDRESS screenshot)
testCleaning('Villa 467B Full', 'Villa 467B - D94, +971 4 395 5591, Dubai, United Arab Emirates');

// Multiline case
testCleaning('Building No. 52 Multiline', "Building No. 52,\n+971 4 279\n8200");

// packed case
testCleaning('Dubai Mall packed', 'The Dubai Mall - Dubai Mall Fashion Avenue Parking - Level 7 - Financial Ctr St, +971 4 449 5111');

// Deep Scrape simulation (phone already found but still in address)
testCleaning('Deep Scrape Simulation', 'Villa 467B - D94, +971 4 395 5591, Dubai, UAE', '+971 4 395 5591');
