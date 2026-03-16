<?php

namespace Tests\Feature;

use App\Models\ScrapingJob;
use App\Scrapers\Runners\ApifyRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ApifyRunnerInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_apify_actor_input_uses_search_strings_array_and_location_query(): void
    {
        $job = ScrapingJob::create([
            'keyword' => 'Famous Food Cafe',
            'location' => 'Kamrej, Surat',
            'radius' => 25,
            'source' => 'apify',
            'status' => 'pending',
        ]);

        $runner = app(ApifyRunner::class);
        $input = $runner->buildActorInput($job);

        $this->assertArrayHasKey('searchStringsArray', $input);
        $this->assertSame(['Famous Food Cafe'], $input['searchStringsArray']);
        $this->assertArrayHasKey('locationQuery', $input);
        $this->assertSame('Kamrej, Surat', $input['locationQuery']);
        $this->assertArrayNotHasKey('queries', $input);
    }

    public function test_apify_actor_input_includes_expected_option_structure(): void
    {
        $job = ScrapingJob::create([
            'keyword' => 'Plumbers',
            'location' => 'London',
            'radius' => 25,
            'source' => 'apify',
            'status' => 'pending',
        ]);

        $runner = app(ApifyRunner::class);
        $input = $runner->buildActorInput($job);

        $this->assertIsArray($input['searchStringsArray']);
        $this->assertIsString($input['locationQuery']);
        $this->assertSame('en', $input['language']);
        $this->assertFalse($input['includeWebResults']);
        $this->assertSame(50, $input['maxCrawledPlacesPerSearch']);
        $this->assertFalse($input['scrapePlaceDetailPage']);
        $this->assertFalse($input['scrapeContacts']);
        $this->assertTrue($input['scrapeReviewsPersonalData']);
        $this->assertFalse($input['skipClosedPlaces']);

        $this->assertArrayHasKey('scrapeSocialMediaProfiles', $input);
        $this->assertIsArray($input['scrapeSocialMediaProfiles']);
        $this->assertFalse($input['scrapeSocialMediaProfiles']['facebooks']);
        $this->assertFalse($input['scrapeSocialMediaProfiles']['instagrams']);
        $this->assertFalse($input['scrapeSocialMediaProfiles']['tiktoks']);
        $this->assertFalse($input['scrapeSocialMediaProfiles']['twitters']);
        $this->assertFalse($input['scrapeSocialMediaProfiles']['youtubes']);
    }

    public function test_apify_actor_input_respects_config_max_crawled_and_actor_input_override(): void
    {
        Config::set('apify.max_crawled_places_per_search', 75);
        Config::set('apify.actor_input', [
            'language' => 'de',
            'scrapePlaceDetailPage' => true,
        ]);

        $job = ScrapingJob::create([
            'keyword' => 'Cafe',
            'location' => 'Berlin',
            'radius' => 25,
            'source' => 'apify',
            'status' => 'pending',
        ]);

        $runner = app(ApifyRunner::class);
        $input = $runner->buildActorInput($job);

        $this->assertSame(75, $input['maxCrawledPlacesPerSearch']);
        $this->assertSame('de', $input['language']);
        $this->assertTrue($input['scrapePlaceDetailPage']);
        $this->assertSame(['Cafe'], $input['searchStringsArray']);
        $this->assertSame('Berlin', $input['locationQuery']);
    }
}
