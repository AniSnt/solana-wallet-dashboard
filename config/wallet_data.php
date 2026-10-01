<?php

return [
    // fake: reads JSON fixtures, no network and no API key. solscan: real calls.
    'driver' => env('SOLSCAN_DRIVER', 'fake'),

    'solscan' => [
        'base_url' => 'https://pro-api.solscan.io/v2.0',
        'api_key' => env('SOLSCAN_API_KEY'),
        'timeout' => 10,        // seconds, explicit on every call
        'attempts' => 3,        // total tries, only for connection errors and 5xx
        'retry_sleep_ms' => 200,
        'cooldown' => 15,       // seconds without calling the API after a 429
    ],

    'fixtures_path' => base_path('tests/Fixtures/solscan'),
];
