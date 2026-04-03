<?php

namespace App\Console\Commands;

use App\Jobs\EnrichBusinessJob;
use App\Models\Business;
use Illuminate\Console\Command;

class MassEnrichCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'businesses:mass-enrich 
                            {--limit=100 : Maximum number of businesses to queue}
                            {--force : Re-enrich even if they already have data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue enrichment jobs for businesses missing emails or websites.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = Business::query();

        if (! $force) {
            // Find businesses missing emails OR missing social links AND have a website
            // OR find businesses missing a website entirely to trigger discovery
            $query->where(function ($q) {
                $q->whereDoesntHave('businessEmails')
                  ->orWhereDoesntHave('socialLinks')
                  ->orWhereNull('website');
            });
        }

        $totalCount = $query->count();
        $businesses = $query->latest()->limit($limit)->get();

        if ($businesses->isEmpty()) {
            $this->info('No businesses found requiring enrichment.');
            return 0;
        }

        $this->info("Found {$totalCount} businesses requiring enrichment. Queueing {$businesses->count()} jobs...");

        $bar = $this->output->createProgressBar($businesses->count());
        $bar->start();

        foreach ($businesses as $business) {
            EnrichBusinessJob::dispatch($business->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->success("Successfully queued {$businesses->count()} enrichment jobs.");

        return 0;
    }

    private function success(string $string): void
    {
        $this->info($string);
    }
}
