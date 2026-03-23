<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkTest extends TestCase
{
    use RefreshDatabase; // Added for database tests

    /**
     * A basic feature test example.
     */
    public function test_api_returns_links_from_database(): void
    {
        $business = Business::factory()->create();

        SocialLink::create([
            'business_id' => $business->id,
            'platform' => 'facebook',
            'url' => 'https://facebook.com/db-link',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/social-links?business_id={$business->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'platform' => 'facebook',
                'url' => 'https://facebook.com/db-link',
            ]);
    }

    public function test_api_returns_empty_array_if_no_links_in_db(): void
    {
        $business = Business::factory()->create();

        $response = $this->getJson("/api/social-links?business_id={$business->id}");

        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_api_returns_404_if_business_not_found(): void
    {
        $response = $this->getJson('/api/social-links?business_id=99999');
        $response->assertStatus(404);
    }
}
