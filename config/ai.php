<?php

return [
    'enabled' => env('AI_ENABLED', true),

    'consensus' => [
        'min_confidence' => env('AI_MIN_CONFIDENCE', 60),
        'timeout_seconds' => env('AI_TIMEOUT_SECONDS', 30),
        'retry_attempts' => env('AI_RETRY_ATTEMPTS', 1),
        'weighted_median' => env('AI_WEIGHTED_MEDIAN', true),

        // LAB/PROD ayrışmalı sapma eşikleri
        'deviation_threshold' => env('AI_DEVIATION_THRESHOLD', 0.20), // %20 sapma vetosu
        'deviation_threshold_lab' => env('AI_DEVIATION_THRESHOLD_LAB', 0.20), // LAB: %20
        'deviation_threshold_prod' => env('AI_DEVIATION_THRESHOLD_PROD', 0.15), // PROD: %15

        // Dinamik sapma eşiği (volatiliteye bağlı)
        'dynamic_threshold_enabled' => env('AI_DYNAMIC_THRESHOLD_ENABLED', false),
        'dynamic_threshold_multiplier' => env('AI_DYNAMIC_THRESHOLD_MULTIPLIER', 1.0),
        'dynamic_threshold_min' => env('AI_DYNAMIC_THRESHOLD_MIN', 0.10), // %10
        'dynamic_threshold_max' => env('AI_DYNAMIC_THRESHOLD_MAX', 0.30), // %30

        // Rate-limit ve circuit breaker
        'rate_limit_enabled' => env('AI_CONSENSUS_RATE_LIMIT_ENABLED', true),
        'max_veto_per_minute' => env('AI_MAX_VETO_PER_MINUTE', 10),
        'circuit_breaker_enabled' => env('AI_CIRCUIT_BREAKER_ENABLED', true),
        'circuit_breaker_threshold' => env('AI_CIRCUIT_BREAKER_THRESHOLD', 5), // 5 veto
        'circuit_breaker_cooldown_seconds' => env('AI_CIRCUIT_BREAKER_COOLDOWN', 30),

        // Validation parametreleri
        'leverage_min' => env('AI_LEVERAGE_MIN', 3),
        'leverage_max' => env('AI_LEVERAGE_MAX', 75),
        'strict_validation' => env('AI_STRICT_VALIDATION', true),

        // Logging ve monitoring
        'structured_logging' => env('AI_STRUCTURED_LOGGING', true),
        'log_context_fields' => ['symbol', 'timeframe', 'provider_id', 'latency_ms'],
        'alert_threshold_minor' => env('AI_ALERT_THRESHOLD_MINOR', 1), // Tek metrik sapması
        'alert_threshold_major' => env('AI_ALERT_THRESHOLD_MAJOR', 3), // Çoklu metrik sapması
    ],

    'providers' => [
        'openai' => [
            'enabled' => env('OPENAI_ENABLED', false),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
            'temperature' => env('OPENAI_TEMPERATURE', 0.1),
            'timeout_ms' => env('OPENAI_TIMEOUT_MS', 60000),
            'cost_per_1k_tokens' => env('OPENAI_COST_PER_1K_TOKENS', '0.03'),
        ],

        'gemini' => [
            'enabled' => env('GEMINI_ENABLED', false),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'max_tokens' => env('GEMINI_MAX_TOKENS', 1000),
            'temperature' => env('GEMINI_TEMPERATURE', 0.1),
            'timeout_ms' => env('GEMINI_TIMEOUT_MS', 60000),
            'cost_per_1k_tokens' => env('GEMINI_COST_PER_1K_TOKENS', '0.0'),
        ],

        'grok' => [
            'enabled' => env('GROK_ENABLED', false),
            'api_key' => env('GROK_API_KEY'),
            'model' => env('GROK_MODEL', 'grok-beta'),
            'max_tokens' => env('GROK_MAX_TOKENS', 1000),
            'temperature' => env('GROK_TEMPERATURE', 0.1),
            'timeout_ms' => env('GROK_TIMEOUT_MS', 60000),
            'cost_per_1k_tokens' => env('GROK_COST_PER_1K_TOKENS', '0.0'),
        ],
    ],

    'prompts' => [
        'trading_decision' => env('AI_TRADING_DECISION_PROMPT', 'You are a trading AI. Analyze the market data and provide a trading decision.'),
        'risk_assessment' => env('AI_RISK_ASSESSMENT_PROMPT', 'You are a risk management AI. Assess the risk of the proposed trade.'),
    ],

    'rate_limiting' => [
        'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('AI_MAX_REQUESTS_PER_MINUTE', 60),
        'max_requests_per_hour' => env('AI_MAX_REQUESTS_PER_HOUR', 1000),
    ],

    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
        'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'gemini'),
        'max_fallback_attempts' => env('AI_MAX_FALLBACK_ATTEMPTS', 2),
    ],
];
