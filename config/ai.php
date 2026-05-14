<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),

    'default_provider' => env('AI_PROVIDER') ?? 'null',

    'providers' => [
        'null' => [
            'model' => env('AI_MODEL', 'null-model'),
            'timeout' => (int) env('AI_TIMEOUT', 15),
            'retries' => (int) env('AI_RETRIES', 1),
        ],
    ],

    'queues' => [
        'anomaly_analysis' => env('AI_QUEUE_ANOMALY_ANALYSIS', env('DB_QUEUE', 'default')),
    ],
];
