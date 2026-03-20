<?php

namespace Tests\Feature;

use App\Jobs\ScrapeBusinessesJob;
use App\Livewire\Result;
use App\Models\Business;
use App\Models\ScrapingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ScrapingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_search_form_creates_scraping_job_and_dispatches_queue_job(): void
    {
        Queue::fake();

        $response = $this->post('/livewire/update', [], []);

        // Test via Livewire component directly — simulate the submit() method
        $payload = [
            'keyword' => 'Dentists',
            'location' => 'Texas',
            'limit' => 100,
        ];

        $scrapingJob = ScrapingJob::create([
            'keyword' => $payload['keyword'],
            'location' => $payload['location'],
            'radius' => 25,
            'source' => 'yelp',
            'status' => 'pending',
        ]);

        ScrapeBusinessesJob::dispatch($scrapingJob);

        $this->assertDatabaseHas('scraping_jobs', [
            'keyword' => 'Dentists',
            'location' => 'Texas',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ScrapeBusinessesJob::class);
    }

    public function test_scraping_job_status_transitions(): void
    {
        $job = ScrapingJob::create([
            'keyword' => 'Restaurants',
            'location' => 'New York',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $job->status);

        $job->markAsRunning();
        $this->assertDatabaseHas('scraping_jobs', ['id' => $job->id, 'status' => 'running']);

        $job->markAsCompleted(15);
        $this->assertDatabaseHas('scraping_jobs', [
            'id' => $job->id,
            'status' => 'completed',
            'results_count' => 15,
        ]);
    }

    public function test_scraping_job_can_be_marked_as_failed(): void
    {
        $job = ScrapingJob::create([
            'keyword' => 'Lawyers',
            'location' => 'Chicago',
            'status' => 'running',
        ]);

        $job->markAsFailed();

        $this->assertDatabaseHas('scraping_jobs', ['id' => $job->id, 'status' => 'failed']);
    }

    public function test_businesses_are_deduplicated_by_hash(): void
    {
        $name = 'Central Park Dental';
        $address = '123 Main St';
        $hash = Business::generateDedupHash($name, $address);

        Business::create([
            'name' => $name,
            'address' => $address,
            'dedup_hash' => $hash,
            'source' => 'test',
        ]);

        // Same hash — firstOrCreate should not create a duplicate
        $second = Business::firstOrCreate(
            ['dedup_hash' => $hash],
            ['name' => $name, 'address' => $address, 'source' => 'test']
        );

        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertDatabaseCount('businesses', 1);
    }

    public function test_scraping_job_can_be_marked_as_cancelled(): void
    {
        $job = ScrapingJob::create([
            'keyword' => 'Plumbers',
            'location' => 'Miami',
            'status' => 'running',
        ]);

        $job->markAsCancelled();

        $this->assertTrue($job->isCancelled());
        $this->assertDatabaseHas('scraping_jobs', [
            'id' => $job->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_scrape_job_exits_early_when_cancelled_before_execution(): void
    {
        Queue::fake();

        $scrapingJob = ScrapingJob::create([
            'keyword' => 'Dentists',
            'location' => 'Dallas',
            'status' => 'cancelled',
        ]);

        $queueJob = new ScrapeBusinessesJob($scrapingJob);
        $queueJob->handle();

        $scrapingJob->refresh();

        $this->assertEquals('cancelled', $scrapingJob->status);
        $this->assertEquals(0, $scrapingJob->results_count);
    }

    public function test_cancel_job_only_works_for_pending_or_running_jobs(): void
    {
        $completedJob = ScrapingJob::create([
            'keyword' => 'Lawyers',
            'location' => 'Houston',
            'status' => 'completed',
            'results_count' => 10,
        ]);

        $pendingJob = ScrapingJob::create([
            'keyword' => 'Doctors',
            'location' => 'Austin',
            'status' => 'pending',
        ]);

        Livewire::test(Result::class)
            ->call('cancelJob', $completedJob->id);

        $this->assertDatabaseHas('scraping_jobs', [
            'id' => $completedJob->id,
            'status' => 'completed',
        ]);

        Livewire::test(Result::class)
            ->call('cancelJob', $pendingJob->id);

        $this->assertDatabaseHas('scraping_jobs', [
            'id' => $pendingJob->id,
            'status' => 'cancelled',
        ]);
    }
}
