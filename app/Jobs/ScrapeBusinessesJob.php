<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Scrapers\Runners\ApifyRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeBusinessesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly ScrapingJob $scrapingJob) {}

    public function handle(): void
    {
        $this->scrapingJob->refresh();

        if ($this->scrapingJob->isCancelled()) {
            Log::info('Scrape job was cancelled before execution', [
                'job_id' => $this->scrapingJob->id,
            ]);

            return;
        }

        Log::info('Starting scrape job', [
            'job_id' => $this->scrapingJob->id,
            'keyword' => $this->scrapingJob->keyword,
            'location' => $this->scrapingJob->location,
            'source' => $this->scrapingJob->source,
        ]);

        $this->scrapingJob->markAsRunning();

        /** @var ApifyRunner $runner */
        $runner = app(ApifyRunner::class);
        $savedCount = $runner->run($this->scrapingJob);

        $this->scrapingJob->refresh();

        if ($this->scrapingJob->isCancelled()) {
            Log::info('Scrape job was cancelled during execution', [
                'job_id' => $this->scrapingJob->id,
                'saved_before_cancel' => $savedCount,
            ]);

            return;
        }

        $this->scrapingJob->markAsCompleted($savedCount);

        Log::info('Scrape job completed', [
            'job_id' => $this->scrapingJob->id,
            'saved' => $savedCount,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Scrape job failed', [
            'job_id' => $this->scrapingJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->scrapingJob->markAsFailed($exception->getMessage());
    }
}
