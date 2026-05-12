<?php

return [
    'defaults' => [
        'locale' => env('TENANT_DEFAULT_LOCALE', env('APP_LOCALE', 'ar')),
        'fallback_locale' => env('TENANT_FALLBACK_LOCALE', env('APP_FALLBACK_LOCALE', 'en')),
        'timezone' => env('TENANT_DEFAULT_TIMEZONE', env('APP_TIMEZONE', 'Africa/Cairo')),
        'currency' => env('TENANT_DEFAULT_CURRENCY', 'EGP'),
    ],

    'features' => [
        'enterprise_accounting' => (bool) env('TENANT_FEATURE_ENTERPRISE_ACCOUNTING', false),
        'maintenance' => (bool) env('TENANT_FEATURE_MAINTENANCE', true),
        'online_payments' => (bool) env('TENANT_FEATURE_ONLINE_PAYMENTS', false),
        'resident_app' => (bool) env('TENANT_FEATURE_RESIDENT_APP', true),
        'ai_features' => (bool) env('TENANT_FEATURE_AI_FEATURES', false),
    ],
];
