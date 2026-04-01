<?php

namespace App\Services;

use App\Jobs\EnrichBusinessJob;
use App\Models\Business;
use App\Models\BusinessEmail;
use App\Models\SocialLink;
use App\Scrapers\Parsers\BusinessParser;
use Illuminate\Support\Facades\Log;

class BusinessService
{
    /**
     * Save a business to the database and trigger enrichment if needed.
     */
    public function saveBusiness(array $data, int $jobId, string $city): ?Business
    {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            return null;
        }

        $website = BusinessParser::cleanWebsiteUrl($data['website'] ?? null);
        $rawPhone = trim($data['phone'] ?? '');
        $rawAddress = trim($data['address'] ?? '');

        $cleaned = BusinessParser::cleanGoogleAddress($rawAddress, $rawPhone);
        $address = $cleaned['address'] ?: $rawAddress;
        $phone = $cleaned['phone'];

        $hash = Business::generateDedupHash($name, $address ?: $city, $city);
        $source = $data['source'] ?? 'unknown';

        try {
            $existing = Business::where('dedup_hash', $hash)->first();

            $updateData = [
                'scraping_job_id' => $jobId,
                'name' => mb_substr($name, 0, 191),
                'city' => $city,
                'source' => $source,
                'cid' => $data['cid'] ?? null,
            ];

            if ($address) {
                $updateData['address'] = $address;
            }
            if ($website && (! $existing || ! $existing->website)) {
                $updateData['website'] = $website;
            }
            if ($phone && (! $existing || ! $existing->phone)) {
                $updateData['phone'] = $phone;
            }

            if (isset($data['category'])) {
                $updateData['category'] = $data['category'];
            }
            if (isset($data['rating'])) {
                $updateData['rating'] = $data['rating'];
            }
            if (isset($data['reviews_count'])) {
                $updateData['reviews_count'] = $data['reviews_count'];
            }
            if (isset($data['latitude'])) {
                $updateData['latitude'] = $data['latitude'];
            }
            if (isset($data['longitude'])) {
                $updateData['longitude'] = $data['longitude'];
            }

            $business = Business::updateOrCreate(['dedup_hash' => $hash], $updateData);

            // Save emails
            if (! empty($data['email']) && is_array($data['email'])) {
                foreach ($data['email'] as $email) {
                    BusinessEmail::firstOrCreate([
                        'business_id' => $business->id,
                        'email' => strtolower(trim($email)),
                    ]);
                }
            }

            // Save social links
            if (! empty($data['socials']) && is_array($data['socials'])) {
                foreach ($data['socials'] as $platform => $url) {
                    if ($url) {
                        SocialLink::updateOrCreate(
                            ['business_id' => $business->id, 'platform' => $platform],
                            ['url' => $url, 'is_active' => true]
                        );
                    }
                }
            }

            $isDirectory = $website && preg_match("/(?:justdial\.com|sulekha\.com|indiamart\.com|tradeindia\.com|yellowpages\.in|yelp\.com|threebestrated\.in|urbanco\.in)/i", $website);

            // Optimization: Only dispatch enrichment if it's a new business or website changed
            // AND only if the source hasn't already provided enrichment data (emails/socials)
            if ($business && ! $isDirectory) {
                $hasEnrichment = $business->businessEmails()->exists() || $business->socialLinks()->exists();

                if (! $hasEnrichment || $business->wasRecentlyCreated || $business->wasChanged('website')) {
                    EnrichBusinessJob::dispatch($business->id)->onQueue('default');
                }
            }

            return $business;
        } catch (\Exception $e) {
            Log::error("Failed to save business: {$name}", ['error' => $e->getMessage()]);

            return null;
        }
    }
}
