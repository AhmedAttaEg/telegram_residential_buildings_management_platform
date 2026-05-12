<?php

return [
    'version' => env('API_VERSION', 'v1'),

    'throttle' => [
        'api' => [
            'per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        ],
        'auth' => [
            'per_minute' => (int) env('API_AUTH_RATE_LIMIT_PER_MINUTE', 5),
        ],
    ],
];
