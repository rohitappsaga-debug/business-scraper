<?php

namespace App\Services;

use App\Integrations\GoogleMaps\GoogleMapsMapper;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private string $apiKey;

    private string $baseUrl = 'https://places.googleapis.com/v1/places:searchText';

    public function __construct(
        private readonly GoogleMapsMapper $mapper
    ) {
        $this->apiKey = config('services.google.maps_api_key') ?? env('GOOGLE_MAPS_API_KEY', '');
    }

    /**
     * Search for places using Google Places API (New).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $keyword, string $location): array
    {
        if (empty($this->apiKey)) {
            Log::error('Google Maps API Key is missing.');

            return [];
        }

        $client = new Client;
        $query = "{$keyword} in {$location}";

        try {
            $response = $client->post($this->baseUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.location',
                ],
                'json' => [
                    'textQuery' => $query,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $places = $data['places'] ?? [];

            Log::info('Google Maps Search: Found '.count($places)." results for '{$query}'");

            return array_map(fn ($place) => $this->mapper->map($place), $places);
        } catch (\Exception $e) {
            Log::error('Google Maps API Error: '.$e->getMessage(), [
                'query' => $query,
            ]);

            return [];
        }
    }
}
