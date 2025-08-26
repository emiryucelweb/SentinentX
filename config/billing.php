<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SaaS Billing Configuration
    |--------------------------------------------------------------------------
    */

    'currency' => env('BILLING_CURRENCY', 'USD'),
    'tax_rate' => env('BILLING_TAX_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    */

    'plans' => [
        'free' => [
            'name' => 'Free Tier',
            'price' => 0,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'features' => [
                'basic_trading',
                'ai_consensus_limited',
                'basic_risk_management',
            ],
            'limits' => [
                'ai_requests' => 100,
                'trades_per_month' => 50,
                'symbols' => 3,
                'api_requests' => 1000,
            ],
            'api_rate_limits' => [
                'requests' => 10,
                'minutes' => 1,
            ],
        ],

        'starter' => [
            'name' => 'Starter Plan',
            'price' => 29,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'features' => [
                'basic_trading',
                'ai_consensus',
                'advanced_risk_management',
                'position_sizing',
                'basic_analytics',
            ],
            'limits' => [
                'ai_requests' => 1000,
                'trades_per_month' => 500,
                'symbols' => 10,
                'api_requests' => 10000,
            ],
            'api_rate_limits' => [
                'requests' => 60,
                'minutes' => 1,
            ],
        ],

        'professional' => [
            'name' => 'Professional Plan',
            'price' => 99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'features' => [
                'advanced_trading',
                'ai_consensus',
                'advanced_risk_management',
                'position_sizing',
                'advanced_analytics',
                'portfolio_management',
                'custom_strategies',
                'priority_support',
            ],
            'limits' => [
                'ai_requests' => 5000,
                'trades_per_month' => 2000,
                'symbols' => 50,
                'api_requests' => 50000,
                'custom_strategies' => 5,
            ],
            'api_rate_limits' => [
                'requests' => 300,
                'minutes' => 1,
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise Plan',
            'price' => 299,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_days' => 30,
            'features' => [
                'enterprise_trading',
                'ai_consensus',
                'enterprise_risk_management',
                'position_sizing',
                'enterprise_analytics',
                'portfolio_management',
                'unlimited_strategies',
                'white_label',
                'dedicated_support',
                'custom_integrations',
            ],
            'limits' => [
                'ai_requests' => -1, // unlimited
                'trades_per_month' => -1,
                'symbols' => -1,
                'api_requests' => -1,
                'custom_strategies' => -1,
            ],
            'api_rate_limits' => [
                'requests' => 1000,
                'minutes' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Tier Configuration
    |--------------------------------------------------------------------------
    */

    'free_tier' => [
        'features' => [
            'basic_trading',
        ],
        'limits' => [
            'ai_requests' => 50,
            'trades_per_month' => 10,
            'symbols' => 1,
            'api_requests' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    */

    'track_usage' => [
        'ai_requests',
        'trades',
        'api_requests',
        'strategy_executions',
        'data_exports',
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', true),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'usage_warning_threshold' => 0.8, // 80% of limit
        'trial_ending_days' => [7, 3, 1], // Days before trial ends
        'payment_failed_retries' => 3,
    ],
];
