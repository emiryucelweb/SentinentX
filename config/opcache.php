<?php

/**
 * OPcache Preloading Configuration for SentientX
 * Preloads critical classes for production performance
 */
$opcacheStatus = opcache_get_status();
if (! (php_sapi_name() === 'cli' && $opcacheStatus && $opcacheStatus['opcache_enabled'])) {
    return;
}

// Laravel framework core
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/Application.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Container/Container.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Http/Request.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Http/Response.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Routing/Router.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php');
opcache_compile_file(__DIR__.'/../vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php');

// SentientX Core Services
$coreClasses = [
    // Trading Core
    'app/Services/Trading/PositionSizer.php',
    'app/Services/Trading/TradeManager.php',
    'app/Services/Trading/StopCalculator.php',
    'app/Services/Trading/ImCapService.php',

    // Risk Management
    'app/Services/Risk/RiskGuard.php',
    'app/Services/Risk/FundingGuard.php',
    'app/Services/Risk/CorrelationService.php',

    // AI Services
    'app/Services/AI/ConsensusService.php',
    'app/Services/AI/ConsensusCalculationService.php',
    'app/Services/AI/AiOutputSchemaService.php',

    // Market Data
    'app/Services/Market/BybitMarketData.php',
    'app/Services/Market/InstrumentInfoService.php',
    'app/Services/Market/AccountService.php',

    // Exchange
    'app/Services/Exchange/BybitClient.php',

    // Core Models
    'app/Models/Trade.php',
    'app/Models/AiLog.php',
    'app/Models/Alert.php',
    'app/Models/User.php',
    'app/Models/Subscription.php',
    'app/Models/Tenant.php',
    'app/Models/UsageCounter.php',

    // DTOs
    'app/DTO/AiDecision.php',

    // SaaS Services
    'app/Services/Billing/SubscriptionService.php',
    'app/Services/Billing/GdprService.php',
    'app/Services/Security/VaultService.php',

    // Controllers (most used)
    'app/Http/Controllers/Controller.php',
    'app/Http/Controllers/SaasDashboardController.php',
    'app/Http/Controllers/GdprController.php',

    // Middleware
    'app/Http/Middleware/TenantMiddleware.php',
    'app/Http/Middleware/RequestLoggingMiddleware.php',
];

$basePath = __DIR__.'/../';

foreach ($coreClasses as $class) {
    $filePath = $basePath.$class;
    if (file_exists($filePath)) {
        try {
            opcache_compile_file($filePath);
        } catch (Throwable $e) {
            // Ignore compilation errors for individual files
            error_log("OPcache preload failed for {$filePath}: ".$e->getMessage());
        }
    }
}

// Preload commonly used vendor packages
$vendorFiles = [
    'vendor/guzzlehttp/guzzle/src/Client.php',
    'vendor/monolog/monolog/src/Logger.php',
    'vendor/symfony/console/Application.php',
    'vendor/symfony/http-foundation/Request.php',
    'vendor/symfony/http-foundation/Response.php',
];

foreach ($vendorFiles as $file) {
    $filePath = $basePath.$file;
    if (file_exists($filePath)) {
        try {
            opcache_compile_file($filePath);
        } catch (Throwable $e) {
            // Ignore vendor compilation errors
        }
    }
}

// Performance logging
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status && isset($status['preload_statistics'])) {
        error_log('OPcache preloading completed. Memory used: '.
                 round($status['memory_usage']['used_memory'] / 1024 / 1024, 2).'MB');
    }
}
