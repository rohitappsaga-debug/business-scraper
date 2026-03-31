<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Services\BusinessService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeBusinessesJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $timeout = 1800;

    public int $tries = 3;

    public ?array $leads = null;

    public ?string $keyword = null;

    public ?string $location = null;

    public ?ScrapingJob $scrapingJob = null;

    public function __construct($leads = null, $keyword = null, $location = null, ?ScrapingJob $scrapingJob = null)
    {
        // Backward compatibility: if the first argument is a ScrapingJob (old dispatch)
        if ($leads instanceof ScrapingJob) {
            $this->scrapingJob = $leads;
            $this->leads = null;
            $this->keyword = $leads->keyword;
            $this->location = $leads->location;
        } else {
            $this->leads = $leads;
            $this->keyword = $keyword;
            $this->location = $location;
            $this->scrapingJob = $scrapingJob;
            // Shorter timeout for individual chunks
            if (! empty($leads)) {
                $this->timeout = 300;
            }
        }
    }

    public function handle(BusinessService $businessService): void
    {
        // Backward compatibility: If we received an old job structure from a previous queue state
        if (! $this->leads && ! $this->keyword && ! $this->scrapingJob) {
            Log::warning('ScrapeBusinessesJob: Legacy or invalid job encountered. Skipping to prevent failure loop.');

            return;
        }

        // ============================================
        // MODE 1: CHUNK WORKER (Process assigned chunk)
        // ============================================
        if ($this->leads) {
            Log::info('Processing chunk of '.count($this->leads)." leads for {$this->keyword} in {$this->location}");

            foreach ($this->leads as $lead) {
                try {
                    // CALL EXISTING SCRAPING/SAVING METHOD (DO NOT MODIFY LOGIC)
                    $businessService->saveBusiness(
                        array_merge($lead, ['source' => 'Hybrid_enriched_v3']),
                        $this->scrapingJob?->id ?? 0,
                        $this->location
                    );
                } catch (\Exception $e) {
                    Log::error('Scrape chunk failed: '.$e->getMessage());
                }
            }

            Log::info("Chunk completed for {$this->keyword} in {$this->location}");

            return;
        }

        // ============================================
        // MODE 2: MASTER ORCHESTRATOR (Fetch & Split)
        // ============================================
        if ($this->scrapingJob) {
            $this->scrapingJob->refresh();
            if ($this->scrapingJob->isCancelled()) {
                return;
            }
            $this->scrapingJob->markAsRunning();
        }

        try {
            $keyword = $this->keyword ?? $this->scrapingJob->keyword;
            $city = $this->location ?? $this->scrapingJob->location;

            $cliPath = base_path('scraper/cli.js');
            $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\" 2>&1";

            Log::info("Executing CLI: {$command}");

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $rawOutput = implode('', $output);

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

            // ⭐ FIX: Split results into chunks and dispatch as a BATCH
            if (! empty($enrichedResults)) {
                $chunks = array_chunk($enrichedResults, 20);
                $jobs = [];

                foreach ($chunks as $chunk) {
                    $jobs[] = new self($chunk, $keyword, $city, $this->scrapingJob);
                }

                $jobRef = $this->scrapingJob;

                Log::info('Dispatching '.count($jobs)." parallel chunk jobs (BATCH) for {$keyword} in {$city}.");

                Bus::batch($jobs)
                    ->name("Scrape: {$keyword} in {$city}")
                    ->then(function (Batch $batch) use ($jobRef) {
                        if ($jobRef) {
                            $jobRef->refresh();
                            $jobRef->markAsCompleted($jobRef->businesses()->count());
                        }
                    })
                    ->finally(function (Batch $batch) use ($jobRef) {
                        // Fallback just in case 'then' didn't trigger correctly
                        if ($jobRef && ! $jobRef->isCompleted()) {
                            $jobRef->markAsCompleted($jobRef->businesses()->count());
                        }
                    })
                    ->onQueue('default')
                    ->dispatch();
            } else {
                if ($this->scrapingJob) {
                    $this->scrapingJob->markAsCompleted(0);
                }
            }

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Job failed: '.$exception->getMessage(), ['job_id' => $this->scrapingJob?->id ?? null]);
        if ($this->scrapingJob && ! $this->leads) {
            $this->scrapingJob->markAsFailed($exception->getMessage());
        }
    }
}
