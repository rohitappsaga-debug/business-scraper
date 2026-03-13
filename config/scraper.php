<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Agent Rotation
    |--------------------------------------------------------------------------
    */
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Delay (milliseconds)
    |--------------------------------------------------------------------------
    */
    'delay_min_ms' => (int) env('SCRAPER_DELAY_MIN_MS', 1500),
    'delay_max_ms' => (int) env('SCRAPER_DELAY_MAX_MS', 3500),

    /*
    |--------------------------------------------------------------------------
    | Crawler Limits
    |--------------------------------------------------------------------------
    */
    'max_depth' => (int) env('SCRAPER_MAX_DEPTH', 2),
    'max_pages' => (int) env('SCRAPER_MAX_PAGES', 50),

    /*
    |--------------------------------------------------------------------------
    | Proxy (optional — leave empty to disable)
    |--------------------------------------------------------------------------
    */
    'proxy' => env('SCRAPER_PROXY', ''),

    /*
    |--------------------------------------------------------------------------
    | Retry
    |--------------------------------------------------------------------------
    */
    'max_retries' => (int) env('SCRAPER_MAX_RETRIES', 3),
];
