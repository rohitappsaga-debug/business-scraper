<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Scrapers\Spiders\BusinessSpider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RoachPHP\Roach;
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

        Log::info('Starting Roach scrape job', [
            'job_id' => $this->scrapingJob->id,
            'keyword' => $this->scrapingJob->keyword,
            'location' => $this->scrapingJob->location,
        ]);

        $this->scrapingJob->markAsRunning();

        try {
            Roach::startSpider(BusinessSpider::class, context: [
                'keyword' => $this->scrapingJob->keyword,
                'city' => $this->scrapingJob->location,
                'job_id' => $this->scrapingJob->id,
            ]);

            $this->scrapingJob->refresh();

            if ($this->scrapingJob->isCancelled()) {
                Log::info('Scrape job was cancelled during execution', [
                    'job_id' => $this->scrapingJob->id,
                ]);

                return;
            }

            $savedCount = $this->scrapingJob->businesses()->count();
            $this->scrapingJob->markAsCompleted($savedCount);

            Log::info('Roach scrape job completed', [
                'job_id' => $this->scrapingJob->id,
                'saved' => $savedCount,
            ]);
        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
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
