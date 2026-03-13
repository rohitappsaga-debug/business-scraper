<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessEmail;
use App\Scrapers\Parsers\BusinessParser;
use App\Scrapers\Parsers\EmailParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExtractEmailsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public readonly Business $business) {}

    public function handle(): void
    {
        $website = $this->business->website;

        if (empty($website)) {
            return;
        }

        if (! str_starts_with($website, 'http')) {
            $website = 'https://'.$website;
        }

        try {
            $client = new Client([
                RequestOptions::HEADERS => [
                    'User-Agent' => BusinessParser::randomUserAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                ],
                RequestOptions::TIMEOUT => 20,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::VERIFY => false,
                RequestOptions::ALLOW_REDIRECTS => ['max' => 4],
            ]);

            $response = $client->get($website);
            $html = (string) $response->getBody();

            $emails = EmailParser::extractFromHtml($html);

            foreach ($emails as $email) {
                BusinessEmail::firstOrCreate(
                    [
                        'business_id' => $this->business->id,
                        'email' => $email,
                    ],
                    ['verified' => false]
                );
            }

            // Set the primary email on the business if none is set
            if (empty($this->business->email) && ! empty($emails)) {
                $this->business->update(['email' => $emails[0]]);
            }

            Log::info('Emails extracted', [
                'business_id' => $this->business->id,
                'found' => count($emails),
            ]);
        } catch (RequestException $e) {
            Log::warning('Failed to fetch website for email extraction', [
                'business_id' => $this->business->id,
                'website' => $website,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
