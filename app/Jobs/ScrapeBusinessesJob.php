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
            // Shorter timeout for individual chunks (now increased for multi-source)
            if (! empty($leads)) {
                $this->timeout = 1800; // Increased to 30 mins to avoid timeouts on large cities
            }
        }
    }

    public function handle(BusinessService $businessService, \App\Services\GeoExpansionService $geoService): void
    {
        // Backward compatibility: If we received an old job structure from a previous queue state
        if (! $this->leads && ! $this->keyword && ! $this->scrapingJob) {
            Log::warning('ScrapeBusinessesJob: Legacy or invalid job encountered. Skipping to prevent failure loop.');

            return;
        }

        // ============================================
        // MODE 0: GEO EXPANDER (New)
        // 💡 CRITICAL: Only expand if NOT already in a batch (to prevent infinite loops)
        // ============================================
        if ($this->batch() === null && $this->scrapingJob && ! $this->leads && $this->location === $this->scrapingJob->location) {
            // Check if this is the first execution (to prevent infinite expansion loops)
            // We'll use a simple check: if it's a COUNTRY/STATE and we haven't dispatched a batch yet.
            // For now, let's just use the expansion service.
            $keyword = $this->scrapingJob->keyword;
            $location = $this->scrapingJob->location;

            $subQueries = $geoService->expand($location);

            if (! empty($subQueries)) {
                Log::info("Expanding '{$location}' into " . count($subQueries) . " sub-queries for keyword '{$keyword}'");
                
                $this->scrapingJob->markAsRunning();
                
                $jobs = collect($subQueries)->map(function ($q) use ($keyword) {
                    // Create a sub-job for each location
                    return new self(null, $keyword, $q['location'], $this->scrapingJob);
                });

                $jobId = $this->scrapingJob->id;
                $batch = \Illuminate\Support\Facades\Bus::batch($jobs->toArray())
                    ->name("Scraping for {$keyword} in {$location}")
                    ->allowFailures() // Don't kill the whole search if one city fails
                    ->catch(function ($batch, Throwable $e) use ($jobId) {
                        $job = \App\Models\ScrapingJob::find($jobId);
                        if ($job) {
                            Log::error("Batch part failed for Job #{$jobId}: " . $e->getMessage());
                            // We do NOT mark as failed here, we let finally() handle the summary.
                        }
                    })
                    ->finally(function ($batch) use ($jobId) {
                        $job = \App\Models\ScrapingJob::find($jobId);
                        if ($job) {
                            $count = $job->businesses()->count();
                            // If we found results, it's a success even if some chunks failed
                            if ($count > 0) {
                                $job->markAsCompleted($count);
                            } else if ($batch->cancelled()) {
                                $job->markAsCancelled();
                            } else if ($batch->failedJobs > 0) {
                                $job->markAsFailed("Search completed with some errors but found no results.");
                            } else {
                                $job->markAsCompleted(0);
                            }
                        }
                    })
                    ->dispatch();

                return;
            }
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
            // Update the current location being searched
            $this->scrapingJob->update(['current_location' => $this->location ?? $this->scrapingJob->location]);
            $this->scrapingJob->markAsRunning();
        }

        try {
            $keyword = $this->keyword ?? $this->scrapingJob->keyword;
            $city = $this->location ?? $this->scrapingJob->location;
            $limit = $this->scrapingJob->limit ?? 100;

            $cliPath = base_path('scraper/cli.js');
            // 💡 REFACTORED: Use configurable node path
            $nodePath = config('scraper.node_path');
            $command = "\"{$nodePath}\" \"{$cliPath}\" \"{$keyword}\" \"{$city}\" {$limit} --mode=scrape";

            Log::info("Executing Streaming CLI: {$command}");

            $descriptorspec = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'],  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                $errorOutput = '';
                stream_set_blocking($pipes[2], false); // 🛡️ STABILITY: Non-blocking stderr to avoid deadlocks

                // Read stdout line by line
                while (! feof($pipes[1])) {
                    $line = fgets($pipes[1]);
                    
                    // Periodically drain stderr during execution
                    $err = stream_get_contents($pipes[2]);
                    if ($err) {
                        $errorOutput .= $err;
                    }

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
                // Capture any remaining stderr
                $errorOutput .= stream_get_contents($pipes[2]);

                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $returnCode = proc_close($process);

                if ($returnCode !== 0) {
                    Log::error("CLI process for {$city} failed with code {$returnCode}. Stderr: {$errorOutput}");
                    // Optionally mark as failed if not in a batch
                    if ($this->batch() === null) {
                        $this->scrapingJob?->markAsFailed("CLI Error (Code {$returnCode}): " . $errorOutput);
                    }
                } else {
                    Log::info("CLI process for {$city} exited successfully");
                }

                if ($this->scrapingJob) {
                    $this->scrapingJob->refresh();
                    
                    // 💡 CRITICAL: Only mark as completed if it's NOT a sub-job in a batch.
                    // For batches, the overall completion is handled by the batch ->finally() callback.
                    if ($this->batch() === null) {
                        $this->scrapingJob->markAsCompleted($this->scrapingJob->businesses()->count());
                    } else {
                        // For sub-jobs, just ensure the count is accurate periodically
                        $this->scrapingJob->update([
                            'results_count' => $this->scrapingJob->businesses()->count()
                        ]);
                    }
                }
            }

        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Job failed: '.$exception->getMessage(), [
            'job_id' => $this->scrapingJob?->id ?? null,
            'location' => $this->location ?? 'Unknown',
            'is_batch' => $this->batch() !== null,
        ]);

        // 💡 ONLY mark as failed if it's NOT a batch sub-job.
        // For batches, the main job failure state is handled in the Mode 0 ->finally() block.
        if ($this->scrapingJob && ! $this->leads && ! $this->batch()) {
            $this->scrapingJob->markAsFailed($exception->getMessage());
        }
    }
}
