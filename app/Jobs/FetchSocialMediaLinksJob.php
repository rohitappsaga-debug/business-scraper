<?php

namespace App\Jobs;

use Apify\Laravel\Facades\Apify;
use App\Models\Business;
use App\Models\SocialLink;
use App\Scrapers\Parsers\BusinessParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchSocialMediaLinksJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $businessId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $business = Business::find($this->businessId);

        if (! $business || empty($business->website)) {
            return;
        }

        $cleanedUrl = BusinessParser::cleanWebsiteUrl($business->website);

        if (! $cleanedUrl) {
            return;
        }

        // Add protocol if missing
        if (! str_starts_with($cleanedUrl, 'http')) {
            $cleanedUrl = 'https://'.$cleanedUrl;
        }

        $cacheKey = 'social_links_'.md5($cleanedUrl);

        if (Cache::has($cacheKey)) {
            Log::info('Social links found in cache', ['url' => $cleanedUrl]);

            return;
        }

        try {
            Log::info('Fetching social links via Apify', ['url' => $cleanedUrl]);

            $run = Apify::runActor('apify~social-media-leads-analyzer', [
                'startUrls' => [['url' => $cleanedUrl]],
                'maxPagesToCrawl' => 5,
            ], [
                'waitForFinish' => 120,
            ]);

            $datasetId = $run['data']['defaultDatasetId'] ?? null;

            if (! $datasetId) {
                Log::warning('Apify actor did not return datasetId', ['run' => $run]);

                return;
            }

            $results = Apify::getDataset($datasetId);
            $items = $results['data']['items'] ?? $results;

            if (empty($items)) {
                Log::info('No social links found by Apify', ['url' => $cleanedUrl]);

                return;
            }

            $item = $items[0]; // Take the first result

            $mappings = [
                'instagram' => ['instagrams', 'instagramUrl'],
                'facebook' => ['facebooks', 'facebookUrl'],
                'linkedin' => ['linkedIns', 'linkedinUrl'],
                'twitter' => ['twitters', 'twitterUrl', 'xUrls'],
                'youtube' => ['youtubes', 'youtubeUrl'],
            ];

            $foundLinks = false;
            foreach ($mappings as $platform => $keys) {
                $url = null;
                foreach ($keys as $key) {
                    $value = $item[$key] ?? null;
                    if (empty($value)) {
                        continue;
                    }

                    if (is_string($value)) {
                        $url = $value;
                        break;
                    }

                    if (is_array($value)) {
                        $first = $value[0] ?? null;
                        if (is_string($first)) {
                            $url = $first;
                            break;
                        }
                        if (is_array($first)) {
                            $url = $first['profileURL'] ?? $first['startUrl'] ?? $first['url'] ?? null;
                            if ($url) {
                                break;
                            }
                        }
                    }
                }

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
                    $foundLinks = true;
                }
            }

            if ($foundLinks) {
                Cache::put($cacheKey, true, now()->addDays(30));
                Log::info('Social links updated for business', ['business_id' => $business->id]);
            }
        } catch (\Throwable $e) {
            Log::error('FetchSocialMediaLinksJob failed', [
                'business_id' => $business->id,
                'url' => $cleanedUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
