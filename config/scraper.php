<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Node Binary Path
    |--------------------------------------------------------------------------
    |
    | This is the path to the node executable. By default, it will attempt
    | to automatically discover Node.js on your system (Windows/Linux).
    | You can override this by setting NODE_BINARY_PATH in your .env.
    |
    */
    'node_path' => env('NODE_BINARY_PATH') ?: \App\Support\NodeFinder::getPath(),

    /*
    |--------------------------------------------------------------------------
    | Scraper Concurrency
    |--------------------------------------------------------------------------
    |
    | The number of concurrent processes or streams the scraper should allow.
    |
    */
    'concurrency' => (int) env('SCRAPER_CONCURRENCY', 5),

    /*
    |--------------------------------------------------------------------------
    | Default Limit
    |--------------------------------------------------------------------------
    |
    | Default number of results to fetch if not specified.
    |
    */
    'default_limit' => 100,
];
