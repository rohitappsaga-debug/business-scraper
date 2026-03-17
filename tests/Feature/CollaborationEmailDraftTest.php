<?php

namespace Tests\Feature;

use App\Ai\Agents\CollaborationEmailDraftAgent;
use App\Livewire\BusinessEmailDraft;
use App\Models\Business;
use App\Models\CollaborationEmailDraft;
use App\Services\AICollaborationDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollaborationEmailDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_draft_using_service(): void
    {
        CollaborationEmailDraftAgent::fake([[
            'subject' => 'Partnership Opportunity',
            'email_body' => 'Hello, we would like to collaborate.',
        ]]);

        $business = Business::factory()->create([
            'name' => 'Test Business',
            'category' => 'Dentist',
            'email' => 'test@example.com',
        ]);

        $service = new AICollaborationDraftService;
        $result = $service->generateDraft($business);

        $this->assertNotEmpty($result['subject']);
        $this->assertNotEmpty($result['email_body']);

        CollaborationEmailDraftAgent::assertPrompted(function ($prompt) {
            return $prompt->contains('Test Business') && $prompt->contains('Dentist');
        });
    }

    public function test_livewire_component_can_generate_and_save_draft(): void
    {
        CollaborationEmailDraftAgent::fake([[
            'subject' => 'Collaboration Idea',
            'email_body' => 'Dear Business, let us work together.',
        ]]);

        $business = Business::factory()->create([
            'name' => 'Awesome Cafe',
            'category' => 'Restaurant',
            'email' => 'cafe@example.com',
        ]);

        Livewire::test(BusinessEmailDraft::class, ['business' => $business])
            ->call('generateDraft')
            ->assertSet('subject', 'Collaboration Idea')
            ->assertSet('emailBody', 'Dear Business, let us work together.')
            ->assertSee('AI Draft generated successfully!');

        $this->assertDatabaseHas('collaboration_email_drafts', [
            'business_id' => $business->id,
            'subject' => 'Collaboration Idea',
            'generated_by_ai' => true,
        ]);
    }

    public function test_can_generate_draft_even_if_email_is_missing(): void
    {
        CollaborationEmailDraftAgent::fake([[
            'subject' => 'Draft Without Email',
            'email_body' => 'This draft was generated without an email address.',
        ]]);

        $business = Business::factory()->create([
            'name' => 'Incomplete Business',
            'email' => null,
        ]);

        Livewire::test(BusinessEmailDraft::class, ['business' => $business])
            ->call('generateDraft')
            ->assertSet('subject', 'Draft Without Email')
            ->assertSet('emailBody', 'This draft was generated without an email address.')
            ->assertSee('AI Draft generated successfully!');

        $this->assertDatabaseHas('collaboration_email_drafts', [
            'business_id' => $business->id,
            'subject' => 'Draft Without Email',
        ]);
    }

    public function test_can_manually_edit_and_save_draft(): void
    {
        $business = Business::factory()->create();

        $draft = CollaborationEmailDraft::create([
            'business_id' => $business->id,
            'subject' => 'Old Subject',
            'email_body' => 'Old Body',
            'generated_by_ai' => true,
        ]);

        Livewire::test(BusinessEmailDraft::class, ['business' => $business])
            ->set('subject', 'New Custom Subject')
            ->set('emailBody', 'New Custom Body')
            ->call('saveDraft')
            ->assertSee('Draft saved successfully!');

        $this->assertDatabaseHas('collaboration_email_drafts', [
            'id' => $draft->id,
            'subject' => 'New Custom Subject',
            'email_body' => 'New Custom Body',
            'generated_by_ai' => false,
        ]);
    }
}
