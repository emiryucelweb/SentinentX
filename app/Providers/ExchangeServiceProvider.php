<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Services\Exchange\AccountService;
use App\Services\Exchange\BybitClient;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Market\BybitMarketData;
use Illuminate\Support\ServiceProvider;

class ExchangeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Exchange Client Interface Binding
        $this->app->bind(ExchangeClientInterface::class, BybitClient::class);

        // BybitClient (legacy support)
        $this->app->singleton(BybitClient::class, function ($app) {
            $config = config('exchange.bybit', []);

            return new BybitClient($config);
        });

        // Exchange services
        $this->app->singleton(AccountService::class);
        $this->app->singleton(InstrumentInfoService::class);

        // Market data services
        $this->app->singleton(BybitMarketData::class);

        // Alias for backward compatibility
        $this->app->alias(BybitClient::class, 'bybit.client');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Exchange configuration validation
        $this->validateExchangeConfig();
    }

    /**
     * Validate exchange configuration
     */
    private function validateExchangeConfig(): void
    {
        $bybitConfig = config('exchange.bybit');

        if (! $bybitConfig) {
            return; // Config file might not exist in testing
        }

        $required = ['api_key', 'api_secret'];
        foreach ($required as $key) {
            if (empty($bybitConfig[$key])) {
                \Log::warning("Bybit config missing required key: {$key}");
            }
        }
    }
}
