<?php

return [
    'enabled' => env('NOTIFICATIONS_ENABLED', true),

    'telegram' => [
        'enabled' => env('TELEGRAM_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    ],

    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_CHANNEL', '#alerts'),
        'username' => env('SLACK_USERNAME', 'SentinentX Bot'),
    ],

    'mail' => [
        'enabled' => env('MAIL_NOTIFICATIONS_ENABLED', false),
        'to' => env('MAIL_NOTIFICATIONS_TO'),
        'from' => env('MAIL_NOTIFICATIONS_FROM', env('MAIL_FROM_ADDRESS')),
        'subject_prefix' => env('MAIL_NOTIFICATIONS_SUBJECT_PREFIX', '[SentinentX]'),
    ],

    'dedup' => [
        'enabled' => env('NOTIFICATION_DEDUP_ENABLED', true),
        'window_seconds' => env('NOTIFICATION_DEDUP_WINDOW', 120),
    ],

    'throttling' => [
        'enabled' => env('NOTIFICATION_THROTTLING_ENABLED', true),
        'max_per_hour' => env('NOTIFICATION_MAX_PER_HOUR', 100),
        'max_per_day' => env('NOTIFICATION_MAX_PER_DAY', 1000),
    ],
];
