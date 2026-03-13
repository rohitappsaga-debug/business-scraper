<?php

namespace App\Jobs;

use App\Models\ScrapingJob;
use App\Scrapers\CrawlerFactory;
use App\Scrapers\Crawlers\DuckDuckGoCrawler;
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
        Log::info('Starting scrape job', [
            'job_id' => $this->scrapingJob->id,
            'keyword' => $this->scrapingJob->keyword,
            'location' => $this->scrapingJob->location,
        ]);

        $this->scrapingJob->markAsRunning();

        // 1. Try the primary source from Factory (now defaults to DuckDuckGo)
        $observer = CrawlerFactory::crawl($this->scrapingJob);
        $savedCount = $observer->getSavedCount();

        // 2. If results are 0, try DuckDuckGo as a forced fallback (if not already used)
        if ($savedCount === 0) {
            Log::info('Primary source yielded 0 results, attempting DuckDuckGo fallback', [
                'job_id' => $this->scrapingJob->id,
            ]);
            $observer = DuckDuckGoCrawler::crawl($this->scrapingJob);
            $savedCount = $observer->getSavedCount();
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

        $this->scrapingJob->markAsFailed();
    }
}
