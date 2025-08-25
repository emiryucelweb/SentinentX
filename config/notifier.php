<?php

declare(strict_types=1);

return [
    'telegram' => [
        'enabled' => true,
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'parse_mode' => 'Markdown',
    ],
    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'webhook' => env('SLACK_WEBHOOK_URL'),
    ],
    'mail' => [
        'enabled' => env('MAIL_NOTIFIER_ENABLED', false),
        'to' => env('MAIL_NOTIFY_TO'),
    ],
];
