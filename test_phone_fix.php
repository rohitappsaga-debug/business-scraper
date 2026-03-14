<?php

require __DIR__.'/vendor/autoload.php';

use App\Scrapers\Parsers\BusinessParser;
use Symfony\Component\DomCrawler\Crawler;

function testParser($label, $rawAddress)
{
    echo "Testing: $label\n";
    echo "Raw: $rawAddress\n";

    // Simulate the Google Maps result node structure
    $html = "<div class='rllt__details'><div></div><div>$rawAddress</div></div>";
    $node = new Crawler($html);

    $result = BusinessParser::parseGoogleMapsResult($node);

    echo 'Address: '.$result['address']."\n";
    echo 'Phone:   '.($result['phone'] ?? 'N/A')."\n";
    echo "----------------------------------------\n";
}

// Cases from user screenshot
testParser('Villa 467B', 'Villa 467B - D94, +971 4 395 5591');
testParser('American Hospital', '12 American Hospital - 15th St, +971 4 377 5500');
testParser('Jumeirah Terrace', '1 10 A Street Jumeirah Terrace - 107 شارع الثاني، من ديسمب، +971 4 379 9954');
testParser('Al Arti Plaza', "Al Arti Plaza - Zaa'beel St, +971 4 605 6056");
