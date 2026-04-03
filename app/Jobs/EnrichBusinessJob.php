<?php

namespace App\Jobs;

use App\Models\Business;
use App\Scrapers\Spiders\BusinessEnrichmentSpider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RoachPHP\Roach;
use Throwable;

class EnrichBusinessJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 600];
    }

    public function __construct(public readonly int $businessId) {}

    public function handle(): void
    {
        $business = Business::with(['socialLinks', 'businessEmails'])->find($this->businessId);

        if (! $business) {
            return;
        }

        // Optimization: If enrichment is already comprehensive, skip.
        if ($business->businessEmails()->exists() && $business->socialLinks()->exists()) {
            Log::info("EnrichBusinessJob: Already enriched {$business->name}. Skipping.");

            return;
        }

        // Ensure the website is a technically valid URL before starting the spider
        $isValidUrl = $business->website && filter_var($business->website, FILTER_VALIDATE_URL) && ! str_contains($business->website, '///');

        if (! $isValidUrl) {
            $this->discoverWebsite($business);
        }

        if (! $business->website || ! filter_var($business->website, FILTER_VALIDATE_URL) || str_contains($business->website, '///')) {
            Log::info("EnrichBusinessJob: No valid website found after discovery for {$business->name}. Skipping.");

            return;
        }

        Log::info("EnrichBusinessJob: Starting enrichment for {$business->name}", [
            'id' => $business->id,
            'website' => $business->website,
        ]);

        try {
            $this->enrichViaBrowser($business);
        } catch (Throwable $e) {
            Log::warning("EnrichBusinessJob: Enrichment failed for {$business->name} ({$business->id}). Error: {$e->getMessage()}");
        }
    }

    /**
     * Enrich the business using the Node.js Playwright-based crawler.
     */
    private function enrichViaBrowser(Business $business): void
    {
        $cliPath = base_path('scraper/cli.js');
        $url = $business->website;
        $name = $business->name;
        $command = "node \"{$cliPath}\" \"{$url}\" \"{$name}\" --mode=enrich-url";

        Log::info("EnrichBusinessJob: Executing Deep Enrichment CLI: {$command}");

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (str_contains($output, '_ENRICH_RESULT_:')) {
                $json = explode('_ENRICH_RESULT_:', $output)[1];
                $data = json_decode(trim($json), true);

                if ($data) {
                    // Update Emails
                    if (! empty($data['emails'])) {
                        foreach ($data['emails'] as $email) {
                            $business->businessEmails()->firstOrCreate(['email' => $email]);
                        }
                        Log::info("EnrichBusinessJob: Found ".count($data['emails'])." emails for {$business->name}");
                    }

                    // Update Social Links
                    if (! empty($data['socials'])) {
                        foreach ($data['socials'] as $platform => $url) {
                            if ($url) {
                                $business->socialLinks()->updateOrCreate(
                                    ['platform' => $platform],
                                    ['url' => $url]
                                );
                            }
                        }
                        Log::info("EnrichBusinessJob: Found social links for {$business->name}");
                    }
                }
            } elseif ($stderr) {
                Log::warning("EnrichBusinessJob: Enrichment CLI error for {$business->name}: {$stderr}");
            }
        }
    }

    private function discoverWebsite(Business $business): void
    {
        $keyword = $business->name;
        $city = $business->city;

        $cliPath = base_path('scraper/cli.js');
        $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\" --mode=discover";

        Log::info("EnrichBusinessJob: Executing Discovery CLI: {$command}");

        try {
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                $output = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                if (str_contains($output, '_DISCOVERY_RESULT_:')) {
                    $json = explode('_DISCOVERY_RESULT_:', $output)[1];
                    $data = json_decode(trim($json), true);

                    if (! empty($data['website'])) {
                        $website = $data['website'];
                        Log::info("EnrichBusinessJob: Discovered website for {$business->name}: {$website}");
                        $business->update(['website' => $website]);
                        $business->refresh();
                    }
                } elseif ($stderr) {
                    Log::warning("EnrichBusinessJob: Discovery CLI error for {$business->name}: {$stderr}");
                }
            }
        } catch (\Exception $e) {
            Log::warning("EnrichBusinessJob: Website discovery failed for {$business->name}: ".$e->getMessage());
        }
    }
}
