<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Services\BusinessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeBusinessesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly ScrapingJob $scrapingJob) {}

    public function handle(BusinessService $businessService): void
    {
        $this->scrapingJob->refresh();
        if ($this->scrapingJob->isCancelled()) {
            return;
        }
        $this->scrapingJob->markAsRunning();

        try {
            $keyword = $this->scrapingJob->keyword;
            $city = $this->scrapingJob->location;

            $cliPath = base_path('scraper/cli.js');
            $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\" 2>&1";

            Log::info("Executing CLI: {$command}");

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $rawOutput = implode('', $output);

            // ⭐ FIX: Extract JSON from output (ignoring logs)
            preg_match('/({.*})/s', $rawOutput, $matches);
            $jsonString = $matches[1] ?? '';

            $result = json_decode($jsonString, true);

            if (! $result || ! isset($result['success'])) {
                throw new \Exception('Failed to parse JSON from scraper output. Raw: '.substr($rawOutput, -500));
            }

            if (! $result['success']) {
                throw new \Exception('Scraper failed: '.($result['error'] ?? 'Unknown error'));
            }

            $enrichedResults = $result['data'] ?? [];
            foreach ($enrichedResults as $bizData) {
                $businessService->saveBusiness(
                    array_merge($bizData, ['source' => 'Hybrid_enriched_v3']),
                    $this->scrapingJob->id,
                    $city
                );
            }

            $this->scrapingJob->refresh();
            $savedCount = $this->scrapingJob->businesses()->count();
            $this->scrapingJob->markAsCompleted($savedCount);

            Log::info('Hybrid Enriched Scrape completed successfully', ['job_id' => $this->scrapingJob->id, 'saved' => $savedCount]);
        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Scrape job failed', ['job_id' => $this->scrapingJob->id, 'error' => $exception->getMessage()]);
        $this->scrapingJob->markAsFailed($exception->getMessage());
    }
}
