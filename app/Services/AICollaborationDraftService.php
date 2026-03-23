<?php

namespace App\Services;

use App\Ai\Agents\CollaborationEmailDraftAgent;
use App\Models\Business;
use Exception;

class AICollaborationDraftService
{
    /**
     * Generate a professional collaboration email draft for a business.
     *
     * @return array{subject: string, email_body: string}
     *
     * @throws Exception
     */
    public function generateDraft(Business $business): array
    {
        $businessName = $business->name;
        $category = $business->category ?? 'Business';
        $user = auth()->user() ?? \App\Models\User::first();

        if (! $user) {
            $user = \App\Models\User::create([
                'name' => 'Default User',
                'email' => 'admin@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]);
        }

        $senderName = $user->settings?->email_sender_name ?? 'Your Team';
        $senderCompany = $user?->settings?->email_sender_name ?? config('app.name', 'Our Platform');
        $senderService = 'marketing, lead generation, and partnership opportunities';

        $prompt = <<<EOT
Write a professional collaboration email to a {$category} business called {$businessName}.

The email should introduce {$senderCompany} and propose a collaboration opportunity that could benefit their business.
We specialize in {$senderService}.

Sender Name: {$senderName}

Tone:
Professional
Friendly
Short (150-200 words)

Structure:
Subject line
Greeting using business name
Short intro
Value proposition tailored to {$category}
Call to action
Professional closing using the provided Sender Name ("{$senderName}") instead of any default or hardcoded name.
EOT;

        $response = retry(3, function () use ($prompt) {
            return CollaborationEmailDraftAgent::make()->prompt($prompt);
        }, 3000);

        return [
            'subject' => $response['subject'],
            'email_body' => $response['email_body'],
        ];
    }
}
