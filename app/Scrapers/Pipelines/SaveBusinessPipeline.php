<?php

namespace App\Scrapers\Pipelines;

use App\Models\Business;
use App\Scrapers\Parsers\BusinessParser;
use App\Scrapers\Spiders\BusinessEnrichmentSpider;
use Illuminate\Support\Facades\Log;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Roach;
use RoachPHP\Support\Configurable;

class SaveBusinessPipeline implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        $data = $item->all();
        $jobId = $data['scraping_job_id'] ?? null;

        // Data Cleaning
        $name = trim($data['name'] ?? '');
        $website = BusinessParser::cleanWebsiteUrl($data['website'] ?? null);
        $rawPhone = trim($data['phone'] ?? '');
        $rawAddress = trim($data['address'] ?? '');
        $city = $data['city'] ?? '';

        if (empty($name)) {
            return $item;
        }

        // Use the parser to clean address and extract/validate phone
        $cleaned = BusinessParser::cleanGoogleAddress($rawAddress, $rawPhone);
        $address = $cleaned['address'] ?: $rawAddress;
        $phone = $cleaned['phone'];

        // Deduplication Hash (Now includes city for 100% accuracy)
        $hash = Business::generateDedupHash($name, $address ?: $city, $city);

        $source = $data['source'] ?? 'web-search';

        try {
            // Find existing to avoid overwriting phone/website with null
            $existing = Business::where('dedup_hash', $hash)->first();
            
            $updateData = [
                'scraping_job_id' => $jobId,
                'name' => mb_substr($name, 0, 191),
                'city' => $city,
                'source' => $source,
            ];

            if ($address) $updateData['address'] = $address;
            if ($website && (! $existing || ! $existing->website)) $updateData['website'] = $website;
            if ($phone && (! $existing || ! $existing->phone)) $updateData['phone'] = $phone;
            if (isset($data['rating'])) $updateData['rating'] = $data['rating'];

            Log::info("Attempting to save binary business: {$name}", ['hash' => $hash, 'job_id' => $jobId]);

            $business = Business::updateOrCreate(
                ['dedup_hash' => $hash],
                $updateData
            );

            Log::info("Saved business successfully: {$name}", ['id' => $business->id]);

            // Trigger Roach Enrichment Spider if website exists
            // Ignore common directory/listicle sites
            if ($website && ! preg_match('/(?:justdial\.com|sulekha\.com|indiamart\.com|tradeindia\.com|yellowpages\.in|yelp\.com|threebestrated\.in|urbanco\.in)/i', $website)) {
                Log::info("Triggering Enrichment for: {$name}", ['website' => $website]);

                Roach::startSpider(BusinessEnrichmentSpider::class, context: [
                    'website' => $website,
                    'business_id' => $business->id,
                ]);
            }

            Log::info("Processed business: {$name}", ['id' => $business->id, 'source' => $source]);
        } catch (\Exception $e) {
            Log::error("Failed to process business: {$name}", ['error' => $e->getMessage()]);
        }

        return $item;
    }
}
