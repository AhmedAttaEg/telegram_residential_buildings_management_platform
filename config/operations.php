<?php

return [
    'shared_hosting' => [
        'queue_worker' => [
            'connection' => env('QUEUE_WORKER_CONNECTION', env('QUEUE_CONNECTION', 'database')),
            'max_time' => (int) env('QUEUE_WORKER_MAX_TIME', 55),
            'sleep' => (int) env('QUEUE_WORKER_SLEEP', 3),
            'tries' => (int) env('QUEUE_WORKER_TRIES', 1),
        ],
    ],

    'backups' => [
        'root' => env('BACKUP_ROOT', storage_path('app/backups')),
        'database' => [
            'dump_binary' => env('BACKUP_DB_DUMP_BINARY', 'mysqldump'),
            'restore_binary' => env('BACKUP_DB_RESTORE_BINARY', 'mysql'),
        ],
        'storage' => [
            'include' => array_filter(array_map('trim', explode(',', (string) env('BACKUP_STORAGE_INCLUDE', 'app,framework,logs')))),
            'exclude' => array_filter(array_map('trim', explode(',', (string) env('BACKUP_STORAGE_EXCLUDE', 'app/backups,framework/cache,framework/sessions,framework/testing,framework/views')))),
        ],
        'retention' => [
            'daily_days' => (int) env('BACKUP_DAILY_RETENTION_DAYS', 7),
            'weekly_weeks' => (int) env('BACKUP_WEEKLY_RETENTION_WEEKS', 4),
        ],
        'schedule' => [
            'run_at' => env('BACKUP_RUN_AT', '02:00'),
            'cleanup_at' => env('BACKUP_CLEANUP_AT', '02:30'),
        ],
    ],
];
