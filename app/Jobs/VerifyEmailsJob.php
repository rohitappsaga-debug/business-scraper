<?php

namespace App\Jobs;

use App\Models\BusinessEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class VerifyEmailsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 2;

    public function __construct(public readonly BusinessEmail $businessEmail) {}

    public function handle(): void
    {
        /**
         * Placeholder: plug in an external email verification API here.
         * Examples: ZeroBounce, NeverBounce, Mailgun Validate.
         *
         * For now, we mark as verified=false and log for future implementation.
         */
        Log::info('Email verification queued (no external API configured)', [
            'business_email_id' => $this->businessEmail->id,
            'email' => $this->businessEmail->email,
        ]);
    }
}
