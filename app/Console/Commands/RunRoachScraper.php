<?php

namespace App\Console\Commands;

use App\Models\ScrapingJob;
use App\Services\BusinessService;
use Illuminate\Console\Command;

class RunRoachScraper extends Command
{
    protected $signature = 'app:run-roach {keyword} {city}';

    protected $description = 'Run the Hybrid Enriched Scraper (Final Stable Version)';

    public function handle(BusinessService $businessService): int
    {
        $keyword = $this->argument('keyword');
        $city = $this->argument('city');

        $this->info("Starting Hybrid Enriched Scrape (Accuracy First) for: {$keyword} in {$city}");

        $job = ScrapingJob::create([
            'keyword' => $keyword,
            'location' => $city,
            'source' => 'hybrid_enriched_v3',
            'status' => 'running',
        ]);

        try {
            $cliPath = base_path('scraper/cli.js');
            $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\" 2>&1";

            $this->info("Executing CLI: {$command}");

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Get exactly one JSON line
            $rawOutput = implode('', $output);

            // ⭐ ACCURACY: Ensure we parse only the JSON block
            preg_match('/({.*})/s', $rawOutput, $matches);
            $jsonString = $matches[1] ?? '';

            $result = json_decode($jsonString, true);

            if (! $result || ! isset($result['success'])) {
                $this->error('Invalid output from Node.js scraper: '.substr($rawOutput, -500));
                $job->markAsFailed('Invalid JSON');

                return 1;
            }

            if (! $result['success']) {
                $this->error('Scraper reported error: '.($result['error'] ?? 'Unknown'));
                $job->markAsFailed($result['error']);

                return 1;
            }

            $enrichedResults = $result['data'] ?? [];
            $this->info('Scraper returned '.count($enrichedResults).' high-quality results.');

            foreach ($enrichedResults as $bizData) {
                $businessService->saveBusiness(
                    array_merge($bizData, ['source' => 'Hybrid_enriched_v3']),
                    $job->id,
                    $city
                );
            }

            $job->refresh();
            $savedCount = $job->businesses()->count();
            $job->markAsCompleted($savedCount);

            $this->info("Success! Saved {$savedCount} businesses.");

            return 0;

        } catch (\Exception $e) {
            $this->error('Critical Failed: '.$e->getMessage());
            $job->markAsFailed($e->getMessage());

            return 1;
        }
    }
}
