<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckScraperHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:check';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Runs a diagnostic check on the scraper environment';

    /**
    * Execute the console command.
    */
    public function handle(): void
    {
        $this->info('--- Scraper Health Check ---');

        $nodePath = config('scraper.node_path');
        $cliPath = base_path('scraper/cli.js');

        $this->info("Node Runtime Discovery:");
        if (env('NODE_BINARY_PATH')) {
            $this->line("  - [CONFIG] Override detected in .env: " . env('NODE_BINARY_PATH'));
        } else {
            $this->line("  - [AUTO] Using automatically discovered path: {$nodePath}");
        }

        if (! \App\Support\NodeFinder::isValid($nodePath)) {
            $this->error("  - [FAIL] Node executable is NOT valid or access is denied at: {$nodePath}");
            $this->error("           Please verify your Node.js installation.");
            return;
        } else {
            $this->info("  - [PASS] Node executable verified.");
        }

        $this->comment("Using CLI: {$cliPath}");

        if (! file_exists($cliPath)) {
            $this->error("[FAIL] Scraper CLI not found at {$cliPath}");

            return;
        }

        $command = "\"{$nodePath}\" \"{$cliPath}\" \"health\" \"check\" --mode=doctor";
        $this->info("Executing command: {$command}");

        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);

            // Extract JSON from output
            if (preg_match('/_JSON_START_(.*?)_JSON_END_/s', $stdout, $matches)) {
                $result = json_decode($matches[1], true);
                if (isset($result['status'])) {
                    $status = $result['status'];
                    $this->line("Node Version: {$status['nodeVersion']}");
                    
                    if ($status['playwright']) {
                        $this->info("[PASS] Playwright Browser: Installed and ready.");
                    } else {
                        $this->error("[FAIL] Playwright Browser: NOT READY.");
                        foreach ($status['errors'] as $error) {
                            $this->warn("  - {$error}");
                        }
                    }
                }
            } else {
                $this->error("[FAIL] Scraper returned invalid output.");
                $this->line("Stdout: {$stdout}");
                $this->line("Stderr: {$stderr}");
            }
            
            if ($returnCode !== 0) {
               $this->error("Diagnostic process exited with code {$returnCode}");
            }
        } else {
            $this->error("[FAIL] Could not start diagnostic process.");
        }

        $this->info('--- Check Complete ---');
    }
}
