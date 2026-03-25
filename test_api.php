<?php

use App\Services\GooglePlacesService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$service = new GooglePlacesService;
$response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
    'query' => 'restaurant in kamrej',
    'key' => config('services.google.maps_api_key'),
]);

echo 'Raw JSON API Response:'.PHP_EOL;
echo $response->body().PHP_EOL;

$result = $response->json();

if (empty($result['results'])) {
    echo 'NO RESULTS FOUND. Status check:'.PHP_EOL;
    print_r($result);
} else {
    echo 'FOUND '.count($result['results']).' RESULTS'.PHP_EOL;
    foreach (array_slice($result['results'], 0, 5) as $index => $place) {
        echo "[$index] ".$place['name'].PHP_EOL;
        echo '    - '.($place['formatted_address'] ?? $place['vicinity'] ?? 'Unknown Address').PHP_EOL;
        echo '    - Rating: '.($place['rating'] ?? 'N/A').PHP_EOL;
    }
}
