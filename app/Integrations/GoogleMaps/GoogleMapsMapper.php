<?php

namespace App\Integrations\GoogleMaps;

class GoogleMapsMapper
{
    /**
     * Map Google Places API (New) response fields to internal format.
     *
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    public function map(array $place): array
    {
        return [
            'name' => $place['displayName']['text'] ?? null,
            'address' => $place['formattedAddress'] ?? null,
            'phone' => $place['nationalPhoneNumber'] ?? $place['internationalPhoneNumber'] ?? null,
            'website' => $place['websiteUri'] ?? null,
            'rating' => $place['rating'] ?? null,
            'reviews_count' => $place['userRatingCount'] ?? null,
            'latitude' => $place['location']['latitude'] ?? null,
            'longitude' => $place['location']['longitude'] ?? null,
            'cid' => $place['id'] ?? null, // Google Place ID
            'source' => 'google-maps',
        ];
    }
}
