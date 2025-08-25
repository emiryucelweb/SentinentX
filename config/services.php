<?php

declare(strict_types=1);

return [
    'ai' => [
        'openai' => [
            'enabled' => env('OPENAI_ENABLED', true),
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout_ms' => 30000,
            'max_tokens' => 2048,
        ],
        'gemini' => [
            'enabled' => env('GEMINI_ENABLED', true),
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout_ms' => 30000,
            'max_tokens' => 2048,
        ],
        'grok' => [
            'enabled' => env('GROK_ENABLED', true),
            'api_key' => env('GROK_API_KEY'),
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'model' => env('GROK_MODEL', 'grok-4-0709'),
            'timeout_ms' => 30000,
            'max_tokens' => 2048,
        ],
    ],
    'coingecko' => [
        'api_key' => env('COINGECKO_API_KEY'),
        'base_url' => env('COINGECKO_BASE_URL', 'https://pro-api.coingecko.com/api/v3'),
        'timeout_ms' => 15000,
    ],
];
