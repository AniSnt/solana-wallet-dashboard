<?php

return [
    // fake: reads JSON fixtures, no network and no API key. solscan: real calls.
    'driver' => env('SOLSCAN_DRIVER', 'fake'),

    'solscan' => [
        'base_url' => 'https://pro-api.solscan.io/v2.0',
        'api_key' => env('SOLSCAN_API_KEY'),
        'timeout' => 10,
        'retries' => 2,
    ],

    'fixtures_path' => base_path('tests/Fixtures/solscan'),
];
