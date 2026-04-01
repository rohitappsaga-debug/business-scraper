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
            $command = "node \"{$cliPath}\" \"{$keyword}\" \"{$city}\"";

            $this->info("Executing Streaming CLI: {$command}");

            $descriptorspec = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'],  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                $count = 0;
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
                            $businessService->saveBusiness(
                                array_merge($bizData, ['source' => 'Hybrid_enriched_v3']),
                                $job->id,
                                $city
                            );

                            $job->increment('results_count');
                            $count++;
                            $this->line("Discovered: {$bizData['name']}");
                        }
                    }

                    // Final Completion Detection (optional fallback)
                    if (str_contains($line, '_JSON_START_')) {
                        // Final summary logic here if needed
                    }
                }

                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                $job->refresh();
                $savedCount = $job->businesses()->count();
                $job->markAsCompleted($savedCount);

                $this->info("Success! Saved {$savedCount} businesses.");

                return 0;
            }

            return 1;

        } catch (\Exception $e) {
            $this->error('Critical Failed: '.$e->getMessage());
            $job->markAsFailed($e->getMessage());

            return 1;
        }
    }
}
