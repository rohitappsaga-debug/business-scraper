<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the settings page renders.
     */
    public function test_settings_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertStatus(200)
            ->assertSeeLivewire(Settings::class);
    }

    /**
     * Test that settings can be saved.
     */
    public function test_settings_can_be_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('emailSenderName', 'Test Sender Name')
            ->call('save')
            ->assertHasNoErrors();

        // Check database directly
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'email_sender_name' => 'Test Sender Name',
        ]);

        // Check fresh instance
        $this->assertEquals('Test Sender Name', $user->fresh()->settings->email_sender_name);
    }
}
