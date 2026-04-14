<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Node Binary Path
    |--------------------------------------------------------------------------
    |
    | This is the absolute path to the node executable on the system.
    |
    | Default: C:\nvm4w\nodejs\node.exe
    */
    'node_path' => env('NODE_BINARY_PATH', 'node'),

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
