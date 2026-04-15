<?php

use App\Support\NodeFinder;

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
    'node_path' => env('NODE_BINARY_PATH') ?: NodeFinder::getPath(),

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

    /*
    |--------------------------------------------------------------------------
    | SSL CA Bundle Path
    |--------------------------------------------------------------------------
    |
    | Path to the CA certificate bundle used for SSL verification on outgoing
    | HTTP requests. Defaults to the bundled cacert.pem in storage/app/ssl,
    | which works across environments. Override via SSL_CA_BUNDLE_PATH in .env
    | if the server has its own system bundle (e.g. /etc/ssl/certs/ca-bundle.crt).
    |
    */
    'ssl_ca_bundle' => env('SSL_CA_BUNDLE_PATH', storage_path('app/ssl/cacert.pem')),
];
