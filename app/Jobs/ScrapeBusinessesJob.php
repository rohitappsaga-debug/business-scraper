<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Services\BusinessService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
            $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\"";

            Log::info("Executing Streaming CLI: {$command}");

            $descriptorspec = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'],  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // Read stdout line by line
                while (! feof($pipes[1])) {
                    $line = fgets($pipes[1]);
                    if (! $line) {
                        continue;
                    }

                    // 💡 NEW: Stream Row Detection
                    if (str_contains($line, '_STREAM_ROW_:')) {
                        $json = str_replace('_STREAM_ROW_:', '', $line);
                        $bizData = json_decode($json, true);

                        if ($bizData) {
                            $savedBiz = $businessService->saveBusiness(
                                array_merge($bizData, ['source' => 'Hybrid_enriched_v3']),
                                $this->scrapingJob?->id ?? 0,
                                $city
                            );

                            // 💡 OPTIMIZATION: Only increment count if it was a new business
                            // This prevents double-counting when Phase 2 enriches Phase 1 results
                            if ($this->scrapingJob && $savedBiz && $savedBiz->wasRecentlyCreated) {
                                $this->scrapingJob->increment('results_count');
                            }
                        }
                    }

                    // Final Completion Detection (optional fallback)
                    if (str_contains($line, '_JSON_START_')) {
                        // We could capture final summary here if needed
                    }
                }

                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                if ($this->scrapingJob) {
                    $this->scrapingJob->refresh();
                    $this->scrapingJob->markAsCompleted($this->scrapingJob->businesses()->count());
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
