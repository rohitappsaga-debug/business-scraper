<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Scrapers\Spiders\BusinessSpider;
use App\Scrapers\Spiders\GoogleLocalSpider;
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

        $this->scrapingJob->markAsRunning();

        try {
            Log::info('Starting Google Local free search', [
                'job_id' => $this->scrapingJob->id,
                'keyword' => $this->scrapingJob->keyword,
                'location' => $this->scrapingJob->location,
            ]);

            // 1. Primary Source: Google Local Scraper (Free)
            Roach::startSpider(GoogleLocalSpider::class, context: [
                'keyword' => $this->scrapingJob->keyword,
                'city' => $this->scrapingJob->location,
                'job_id' => $this->scrapingJob->id,
            ]);

            $this->scrapingJob->refresh();
            $savedCount = $this->scrapingJob->businesses()->count();

            if ($savedCount > 0) {
                $this->scrapingJob->markAsCompleted($savedCount);
                Log::info('Google Local search completed successfully', [
                    'job_id' => $this->scrapingJob->id,
                    'saved' => $savedCount,
                ]);

                return;
            }

            // 2. Fallback: Roach Spiders (JustDial, Sulekha etc.)
            Log::info('Google Local returned 0 results. Falling back to Roach spiders.', [
                'job_id' => $this->scrapingJob->id,
            ]);

            Roach::startSpider(BusinessSpider::class, context: [
                'keyword' => $this->scrapingJob->keyword,
                'city' => $this->scrapingJob->location,
                'job_id' => $this->scrapingJob->id,
            ]);

            $this->scrapingJob->refresh();

            if ($this->scrapingJob->isCancelled()) {
                Log::info('Scrape job was cancelled during fallback execution', [
                    'job_id' => $this->scrapingJob->id,
                ]);

                return;
            }

            $savedCount = $this->scrapingJob->businesses()->count();
            $this->scrapingJob->markAsCompleted($savedCount);

            Log::info('Fallback scrape job completed', [
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
