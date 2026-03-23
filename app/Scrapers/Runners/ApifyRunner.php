<?php

namespace App\Scrapers\Runners;

use Apify\Laravel\ApifyException;
use Apify\Laravel\Facades\Apify;
use App\Integrations\Apify\ApifyGoogleMapsMapper;
use App\Jobs\ExtractEmailsJob;
use App\Jobs\FetchSocialMediaLinksJob;
use App\Models\Business;
use App\Models\ScrapingJob;
use App\Models\SocialLink;
use App\Scrapers\Parsers\BusinessParser;
use Illuminate\Support\Facades\Log;

class ApifyRunner
{
    public function __construct(private readonly ApifyGoogleMapsMapper $mapper) {}

    public function run(ScrapingJob $job): int
    {
        set_time_limit(0);
        $actorId = (string) (config('apify.actor_id') ?? config('services.apify.actor_id') ?? '');
        if ($actorId === '') {
            $actorId = 'compass~crawler-google-places';
        }
        $actorId = str_replace('/', '~', $actorId);

        $waitForFinish = (int) (config('apify.default_actor_options.waitForFinish') ?? 60);

        $input = $this->buildActorInput($job);

        try {
            $run = Apify::runActor($actorId, $input, [
                'waitForFinish' => $waitForFinish,
            ]);
        } catch (ApifyException $e) {
            Log::error('Apify runActor failed', [
                'job_id' => $job->id,
                'actor_id' => $actorId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $datasetId = $this->extractDatasetId($run);
        if (! $datasetId) {
            throw new \RuntimeException('Apify did not return defaultDatasetId.');
        }

        $saved = 0;
        $offset = 0;
        $limit = 100;

        while (true) {
            $job->refresh();

            if ($job->isCancelled()) {
                Log::info('Scrape job cancelled during dataset fetch', [
                    'job_id' => $job->id,
                    'saved_so_far' => $saved,
                ]);

                break;
            }

            $page = Apify::getDataset($datasetId, [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            $items = $this->extractItems($page);
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $saved += $this->persistItem($job, $item);
            }

            $offset += $limit;
        }

        return $saved;
    }

    /**
     * Build actor input matching the working Apify config (searchStringsArray + locationQuery).
     *
     * @return array<string, mixed>
     */
    public function buildActorInput(ScrapingJob $job): array
    {
        $maxCrawled = (int) (config('apify.max_crawled_places_per_search') ?? 50);

        $input = [
            'includeWebResults' => false,
            'language' => 'en',
            'locationQuery' => trim($job->location),
            'maxCrawledPlacesPerSearch' => $maxCrawled,
            'maxImages' => 0,
            'maximumLeadsEnrichmentRecords' => 0,
            'scrapeContacts' => false,
            'scrapeDirectories' => false,
            'scrapeImageAuthors' => false,
            'scrapePlaceDetailPage' => false,
            'scrapeReviewsPersonalData' => true,
            'scrapeSocialMediaProfiles' => [
                'facebooks' => true,
                'instagrams' => true,
                'tiktoks' => true,
                'twitters' => true,
                'youtubes' => true,
            ],
            'scrapeTableReservationProvider' => false,
            'searchStringsArray' => [trim($job->keyword)],
            'skipClosedPlaces' => false,
        ];

        $overrides = config('apify.actor_input', []);
        if (is_array($overrides) && $overrides !== []) {
            $input = array_replace_recursive($input, $overrides);
        }

        return $input;
    }

    private function extractDatasetId(mixed $run): ?string
    {
        if (is_array($run)) {
            $data = $run['data'] ?? $run;
            $id = $data['defaultDatasetId'] ?? null;
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return list<mixed>
     */
    private function extractItems(mixed $datasetResponse): array
    {
        if (is_array($datasetResponse)) {
            if (array_key_exists('data', $datasetResponse) && is_array($datasetResponse['data'])) {
                $data = $datasetResponse['data'];
                if (array_key_exists('items', $data) && is_array($data['items'])) {
                    return $data['items'];
                }
            }

            if (array_is_list($datasetResponse)) {
                return $datasetResponse;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function persistItem(ScrapingJob $job, array $item): int
    {
        $mapped = $this->mapper->map($item);

        $name = $mapped['name'] ?? null;
        $rawAddress = $mapped['address'] ?? null;

        if (! is_string($name) || trim($name) === '' || ! is_string($rawAddress) || trim($rawAddress) === '') {
            return 0;
        }

        $cleaned = BusinessParser::cleanGoogleAddress($rawAddress, is_string($mapped['phone'] ?? null) ? $mapped['phone'] : null);

        $name = mb_substr(trim($name), 0, 191);
        $address = mb_substr($cleaned['address'], 0, 191);

        $hash = Business::generateDedupHash($name, $address);

        $existing = Business::where('dedup_hash', $hash)->first();

        $updateData = [
            'scraping_job_id' => $job->id,
            'name' => $name,
            'category' => $mapped['category'] ?? ($existing->category ?? null),
            'address' => $address,
            'city' => $cleaned['city'] ?: $job->location,
            'state' => $cleaned['state'] ?: ($existing->state ?? null),
            'zip' => $cleaned['zip'] ?: ($existing->zip ?? null),
            'country' => $cleaned['country'] ?? ($existing->country ?? null),
            'phone' => $cleaned['phone'],
            'website' => $mapped['website'] ?? ($existing->website ?? null),
            'email' => $mapped['email'] ?? ($existing->email ?? null),
            'rating' => $mapped['rating'] ?? ($existing->rating ?? null),
            'reviews_count' => $mapped['reviews_count'] ?? ($existing->reviews_count ?? null),
            'latitude' => $mapped['latitude'] ?? ($existing->latitude ?? null),
            'longitude' => $mapped['longitude'] ?? ($existing->longitude ?? null),
            'cid' => $mapped['cid'] ?? ($existing->cid ?? null),
            'source' => $job->source,
            'dedup_hash' => $hash,
        ];

        if ($existing) {
            $existing->update($updateData);
            $business = $existing;
        } else {
            $business = Business::create($updateData);
        }

        // Persist Social Links
        if (! empty($mapped['social'])) {
            foreach ($mapped['social'] as $platform => $url) {
                if ($url) {
                    SocialLink::updateOrCreate(
                        [
                            'business_id' => $business->id,
                            'platform' => $platform,
                        ],
                        [
                            'url' => $url,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        if (! empty($business->website)) {
            if ($business->businessEmails()->count() === 0) {
                ExtractEmailsJob::dispatch($business->id)->onQueue('default');
            }

            // Always check for/update social links from website
            FetchSocialMediaLinksJob::dispatch($business->id)->onQueue('default');
        }

        return 1;
    }
}
