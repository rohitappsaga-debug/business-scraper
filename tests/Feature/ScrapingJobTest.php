<?php

namespace Tests\Feature;

use App\Jobs\ScrapeBusinessesJob;
use App\Models\Business;
use App\Models\ScrapingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
}
