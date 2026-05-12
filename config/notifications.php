<?php

return [
    'subscription_reminders' => [
        'expiration_lead_days' => (int) env('SUBSCRIPTION_REMINDER_EXPIRATION_LEAD_DAYS', 7),
        'grace_enabled' => (bool) env('SUBSCRIPTION_REMINDER_GRACE_ENABLED', true),
        'suspension_enabled' => (bool) env('SUBSCRIPTION_REMINDER_SUSPENSION_ENABLED', true),
    ],
];
