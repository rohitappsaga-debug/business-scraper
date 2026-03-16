<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupPlaceholderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-placeholder-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove placeholder emails like you@company.com from the database';

    public function handle(): void
    {
        $placeholders = [
            'you@company.com',
            'test@example.com',
            'info@example.com',
            'admin@example.com',
        ];

        $domainPatterns = [
            '%@company.com',
            '%@example.com',
            '%@test.com',
            '%@domain.com',
            '%@yoursite.com',
        ];

        $count = 0;

        // 1. Remove from BusinessEmail model
        foreach ($placeholders as $email) {
            $count += \App\Models\BusinessEmail::where('email', $email)->delete();
        }

        foreach ($domainPatterns as $pattern) {
            $count += \App\Models\BusinessEmail::where('email', 'like', $pattern)->delete();
        }

        // 2. Clear from Business model (primary email field)
        foreach ($placeholders as $email) {
            \App\Models\Business::where('email', $email)->update(['email' => null]);
        }

        foreach ($domainPatterns as $pattern) {
            \App\Models\Business::where('email', 'like', $pattern)->update(['email' => null]);
        }

        $this->info("Successfully removed {$count} placeholder email records.");
    }
}
