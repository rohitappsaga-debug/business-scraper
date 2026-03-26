<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Manually test the logic from BusinessSpider
class TestSpider {
    public function isHighlyRelevant(?string $address, string $targetCity): bool
    {
        if (!$address) return false; 
        $addr = strtolower($address);
        $city = strtolower($targetCity);
        $irrelevantMetros = ['mumbai', 'thane', 'navi', 'maharashtra', 'pune', 'bangalore'];
        foreach ($irrelevantMetros as $metro) {
            if (str_contains($addr, $metro) && !str_contains($city, $metro)) return false;
        }
        if (str_contains($addr, $city)) return true;
        foreach (explode(' ', $city) as $word) {
            if (strlen($word) > 3 && str_contains($addr, $word)) return true;
        }
        if ($city === 'kamrej' && str_contains($addr, 'surat')) return true;
        return false;
    }
}

$spider = new TestSpider();
$addr = "Ghavri Building Karol Bagh, Delhi";
$city = "bhavnagar";
var_dump($spider->isHighlyRelevant($addr, $city)); // Should be false
