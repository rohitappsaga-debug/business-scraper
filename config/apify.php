<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Apify API Token
    |--------------------------------------------------------------------------
    |
    | Your Apify API token. You can find this in your Apify account settings.
    | It's recommended to store this in your .env file as APIFY_API_TOKEN.
    |
    */
    'api_token' => env('APIFY_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default Actor ID
    |--------------------------------------------------------------------------
    |
    | Optional default Actor to run when using Apify as a source.
    | Use tilde format for API: username~actor-name (e.g. compass~crawler-google-places).
    |
    */
    'actor_id' => env('APIFY_ACTOR_ID'),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | The base URI for Apify API requests. You typically won't need to change this.
    |
    */
    'base_uri' => env('APIFY_BASE_URI', 'https://api.apify.com/v2/'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests. Increase this for long-running
    | operations or if you experience timeout issues.
    |
    */
    'timeout' => env('APIFY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Actor Options
    |--------------------------------------------------------------------------
    |
    | Default options for running actors. These can be overridden when calling
    | the runActor method.
    |
    */
    'default_actor_options' => [
        'waitForFinish' => 60, // seconds
        'memory' => 256, // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Actor Input Defaults
    |--------------------------------------------------------------------------
    |
    | max_crawled_places_per_search: default limit per search (used when building
    | actor input). actor_input: optional array merged over the built input so
    | you can override any key without code changes.
    |
    */
    'max_crawled_places_per_search' => (int) (env('APIFY_MAX_CRAWLED_PLACES_PER_SEARCH') ?: 50),
    'actor_input' => [],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhooks. Set webhook_url to receive notifications
    | when your actors finish running.
    |
    */
    'webhook_url' => env('APIFY_WEBHOOK_URL'),
    'webhook_events' => [
        'ACTOR.RUN.SUCCEEDED',
        'ACTOR.RUN.FAILED',
        'ACTOR.RUN.ABORTED',
    ],
];
