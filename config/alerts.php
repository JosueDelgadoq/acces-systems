<?php

return [
    'fallback_emails' => collect(explode(',', (string) env('ALERT_FALLBACK_EMAILS', '')))
        ->map(fn (string $value): string => trim($value))
        ->filter()
        ->values()
        ->all(),

    'fallback_whatsapp' => collect(explode(',', (string) env('ALERT_FALLBACK_WHATSAPP', '')))
        ->map(fn (string $value): string => trim($value))
        ->filter()
        ->values()
        ->all(),

    'pending_due' => [
        'enabled' => env('ALERT_PENDING_DUE_ENABLED', true),
        'include_assigned_user_email' => env('ALERT_PENDING_DUE_INCLUDE_ASSIGNED_USER_EMAIL', true),
        'include_assigned_user_whatsapp' => env('ALERT_PENDING_DUE_INCLUDE_ASSIGNED_USER_WHATSAPP', true),
        'include_client_email' => env('ALERT_PENDING_DUE_INCLUDE_CLIENT_EMAIL', false),
        'include_client_whatsapp' => env('ALERT_PENDING_DUE_INCLUDE_CLIENT_WHATSAPP', false),
        'send_database_notification' => env('ALERT_PENDING_DUE_SEND_DATABASE_NOTIFICATION', true),
    ],

    'pending_lifecycle' => [
        'enabled' => env('ALERT_PENDING_LIFECYCLE_ENABLED', true),
        'include_assigned_user_email' => env('ALERT_PENDING_LIFECYCLE_INCLUDE_ASSIGNED_USER_EMAIL', true),
        'include_previous_assigned_user_email' => env('ALERT_PENDING_LIFECYCLE_INCLUDE_PREVIOUS_ASSIGNED_USER_EMAIL', true),
        'include_client_email' => env('ALERT_PENDING_LIFECYCLE_INCLUDE_CLIENT_EMAIL', false),
        'send_database_notification' => env('ALERT_PENDING_LIFECYCLE_SEND_DATABASE_NOTIFICATION', true),
    ],

    'commercial' => [
        'enabled' => env('ALERT_COMMERCIAL_ENABLED', true),
        'include_commercial_email' => env('ALERT_COMMERCIAL_INCLUDE_COMMERCIAL_EMAIL', true),
        'include_management_email' => env('ALERT_COMMERCIAL_INCLUDE_MANAGEMENT_EMAIL', true),
        'stale_days' => (int) env('ALERT_COMMERCIAL_STALE_DAYS', 3),
        'goal_progress_tolerance' => (int) env('ALERT_COMMERCIAL_GOAL_PROGRESS_TOLERANCE', 15),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
        'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),
    ],
];
