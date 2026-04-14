<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapingJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ScraperDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a comprehensive diagnostic report for the scraper';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $reportPath = base_path('scraper_debug_report.txt');
        $output = "============== SCRAPER DIAGNOSTIC REPORT ==============\n";
        $output .= "Generated AT: " . now()->format('Y-m-d H:i:s') . "\n";
        $output .= "Base Path: " . base_path() . "\n\n";

        // 1. Environment Check
        $output .= "--- 1. ENVIRONMENT ---\n";
        $output .= "OS: " . PHP_OS . "\n";
        $output .= "PHP Version: " . PHP_VERSION . "\n";
        $output .= "Scraper Config (node_path): " . config('scraper.node_path') . "\n";
        
        $nodeCheck = @shell_exec('node -v 2>&1');
        $output .= "Global Node Check: " . trim($nodeCheck ?: 'Command not found') . "\n";
        
        $configuredNode = config('scraper.node_path');
        $versionCheck = @shell_exec("\"{$configuredNode}\" -v 2>&1");
        $output .= "Configured Node Check: " . trim($versionCheck ?: 'Binary not found at path') . "\n\n";

        // 2. Database & Jobs
        $output .= "--- 2. DATABASE & JOBS ---\n";
        try {
            DB::connection()->getPdo();
            $output .= "DB Connection: SUCCESS\n";
            
            $lastJobs = ScrapingJob::orderBy('id', 'desc')->limit(10)->get();
            if ($lastJobs->isEmpty()) {
                $output .= "No Jobs found in database.\n";
            } else {
                foreach ($lastJobs as $job) {
                    $output .= sprintf(
                        "Job #%-4d | %-15s | %-15s | Status: %-10s | Res: %-4d | Error: %s\n",
                        $job->id, 
                        $job->keyword, 
                        $job->location, 
                        $job->status, 
                        $job->results_count, 
                        $job->error_message ?: 'NONE'
                    );
                }
            }
        } catch (\Exception $e) {
            $output .= "DB Connection: FAILED (" . $e->getMessage() . ")\n";
        }
        $output .= "\n";

        // 3. Scraper Doctor Check
        $output .= "--- 3. SCRAPER DOCTOR CHECK ---\n";
        $cliPath = base_path('scraper/cli.js');
        if (File::exists($cliPath)) {
            $doctorOutput = @shell_exec("\"{$configuredNode}\" \"{$cliPath}\" --mode=doctor 2>&1");
            $output .= ($doctorOutput ?: "No output from doctor mode.") . "\n";
        } else {
            $output .= "CRITICAL: scraper/cli.js NOT FOUND!\n";
        }
        $output .= "\n";

        // 4. Recent Logs (Memory efficient reading)
        $output .= "--- 4. RECENT ERROR LOGS (Last 20 lines) ---\n";
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $handle = fopen($logPath, "r");
            $lines = [];
            fseek($handle, 0, SEEK_END);
            $pos = ftell($handle);
            $count = 0;
            
            while ($pos > 0 && $count < 21) {
                fseek($handle, --$pos);
                $char = fgetc($handle);
                if ($char === "\n") {
                    $count++;
                }
            }
            
            while (!feof($handle)) {
                $lines[] = fgets($handle);
            }
            fclose($handle);
            
            $output .= implode("", array_filter($lines)) . "\n";
        } else {
            $output .= "No laravel.log found.\n";
        }

        File::put($reportPath, $output);
        
        $this->line($output);
        $this->info("\nDiagnostic report saved to: " . $reportPath);
        $this->comment("Please copy the result above or the contents of the file and send it back to me.");

        if ($this->laravel->runningInConsole()) {
            $this->newLine();
            $this->ask('Press [Enter] to close this window');
        }
    }
}
