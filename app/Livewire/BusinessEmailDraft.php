<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\CollaborationEmailDraft;
use App\Services\AICollaborationDraftService;
use Exception;
use Illuminate\View\View;
use Livewire\Component;

class BusinessEmailDraft extends Component
{
    public Business $business;

    public ?int $draftId = null;

    public string $subject = '';

    public string $emailBody = '';

    public bool $isEditing = false;

    public bool $isGenerating = false;

    public function mount(Business $business): void
    {
        $this->business = $business;
        $this->loadDraft();
    }

    public function loadDraft(): void
    {
        $draft = $this->business->collaborationEmailDraft;

        if ($draft) {
            $this->draftId = $draft->id;
            $this->subject = $draft->subject;
            $this->emailBody = $draft->email_body;
        } else {
            $this->draftId = null;
            $this->subject = '';
            $this->emailBody = '';
        }
    }

    public function generateDraft(AICollaborationDraftService $service): void
    {
        if (empty($this->business->name)) {
            session()->flash('error', 'Business name is required to generate a draft.');

            return;
        }

        $this->isGenerating = true;

        try {
            $result = $service->generateDraft($this->business);

            $draft = CollaborationEmailDraft::updateOrCreate(
                ['business_id' => $this->business->id],
                [
                    'subject' => $result['subject'],
                    'email_body' => $result['email_body'],
                    'generated_by_ai' => true,
                ]
            );

            $this->draftId = $draft->id;
            $this->subject = $draft->subject;
            $this->emailBody = $draft->email_body;

            session()->flash('success', 'AI Draft generated successfully!');
        // } catch (Exception $e) {
        //     $message = $e->getMessage();
        //     if (str_contains(strtolower($message), 'rate limit')) {
        //         $message = 'Gemini AI is temporarily busy. Please wait a few seconds and try again.';
        //     }
        //     session()->flash('error', 'Failed to generate draft: '.$message);
        } finally {
            $this->isGenerating = false;
        }
    }

    public function copySubject(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->subject);
        session()->flash('success', 'Subject line copied to clipboard!');
    }

    public function copyBody(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->emailBody);
        session()->flash('success', 'Email body copied to clipboard!');
    }

    public function toggleEdit(): void
    {
        $this->isEditing = ! $this->isEditing;
    }

    public function saveDraft(): void
    {
        $this->validate([
            'subject' => 'required|string|max:255',
            'emailBody' => 'required|string',
        ]);

        if ($this->draftId) {
            $draft = CollaborationEmailDraft::findOrFail($this->draftId);
            $draft->update([
                'subject' => $this->subject,
                'email_body' => $this->emailBody,
                'generated_by_ai' => false,
            ]);
        } else {
            $draft = CollaborationEmailDraft::create([
                'business_id' => $this->business->id,
                'subject' => $this->subject,
                'email_body' => $this->emailBody,
                'generated_by_ai' => false,
            ]);
            $this->draftId = $draft->id;
        }

        $this->isEditing = false;
        session()->flash('success', 'Draft saved successfully!');
    }

    public function regenerate(AICollaborationDraftService $service): void
    {
        $this->generateDraft($service);
    }

    public function copyToClipboard(): void
    {
        $this->copyBody();
    }

    public function render(): View
    {
        return view('livewire.business-email-draft');
    }
}
