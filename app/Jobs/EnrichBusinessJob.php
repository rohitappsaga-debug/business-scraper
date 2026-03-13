<?php

namespace App\Jobs;

use App\Models\Business;
use App\Scrapers\Parsers\BusinessParser;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnrichBusinessJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Business $business) {}

    public function handle(): void
    {
        Log::info('Enriching business data', ['id' => $this->business->id, 'address' => $this->business->address]);

        $this->enrichLocation();
        $this->enrichDescription();

        $this->business->save();
    }

    private function enrichLocation(): void
    {
        $apiKey = config('services.google_maps.key');
        $address = $this->business->address;

        if ($apiKey && ! empty($address)) {
            try {
                $client = new Client(['verify' => false]);
                $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'query' => [
                        'address' => $address,
                        'key' => $apiKey,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (($data['status'] ?? '') === 'OK' && ! empty($data['results'])) {
                    $components = $data['results'][0]['address_components'];
                    $geometry = $data['results'][0]['geometry']['location'];

                    foreach ($components as $component) {
                        $types = $component['types'];
                        if (in_array('locality', $types)) {
                            $this->business->city = $component['long_name'];
                        } elseif (in_array('administrative_area_level_1', $types)) {
                            $this->business->state = $component['long_name'];
                        } elseif (in_array('postal_code', $types)) {
                            $this->business->postal_code = $component['long_name'];
                        } elseif (in_array('country', $types)) {
                            $this->business->country = $component['short_name'];
                        }
                    }

                    $this->business->latitude = $geometry['lat'];
                    $this->business->longitude = $geometry['lng'];

                    return;
                }
            } catch (\Exception $e) {
                Log::warning('Geocoding failed, falling back to regex', ['error' => $e->getMessage()]);
            }
        }

        $this->fallbackLocationParsing();
    }

    private function fallbackLocationParsing(): void
    {
        $address = $this->business->address;

        // If we have a scraping job, use its location as a primary fallback for city
        $jobLocation = $this->business->scrapingJob?->location;
        if (! empty($jobLocation) && empty($this->business->city)) {
            $this->business->city = $jobLocation;
        }

        if (empty($address) || $address === 'Captured via fallback') {
            return;
        }

        $parts = array_map('trim', explode(',', $address));
        $count = count($parts);

        // More cautious city/state extraction
        if ($count >= 2) {
            if (empty($this->business->city)) {
                $this->business->city = $parts[$count - 2];
            }
            if (empty($this->business->state)) {
                $this->business->state = $parts[$count - 1];
            }
        }

        if (preg_match('/\b(\d{5,6})\b/', $address, $matches)) {
            $this->business->postal_code = $matches[1];
        }
    }

    private function enrichDescription(): void
    {
        if (empty($this->business->description) || $this->business->description === 'No description available.') {
            $this->business->description = BusinessParser::generateFallbackDescription(
                $this->business->name,
                $this->business->category ?? 'Local Business',
                $this->business->address ?? ''
            );
        }
    }
}
