<?php

namespace App\Providers;

use App\Contracts\Lab\MetricsServiceInterface;
use App\Contracts\Notifier\AlertDispatcher as AlertDispatcherContract;
use App\Contracts\Risk\RiskGuardInterface;
use App\Contracts\Support\LockManager;
use App\Services\Lab\MetricsService;
use App\Services\Notifier\AlertDispatcher;
use App\Services\Risk\CorrelationService;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuard;
use App\Services\Support\CacheLockManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // MetricsService interface binding
        $this->app->bind(MetricsServiceInterface::class, MetricsService::class);

        // Notifier (interface → concrete)
        $this->app->bind(AlertDispatcherContract::class, function ($app) {
            $enabled = (bool) config('notifications.enabled', true);
            $telegram = config('notifications.telegram.enabled', false)
                ? $app->make(\App\Services\Notifier\TelegramNotifier::class)
                : null;

            return new AlertDispatcher($telegram, $enabled);
        });

        // Risk services (interface → concrete)
        $this->app->bind(RiskGuardInterface::class, RiskGuard::class);
        $this->app->singleton(FundingGuard::class, FundingGuard::class);
        $this->app->singleton(CorrelationService::class, CorrelationService::class);

        // LockManager (interface → cache tabanlı uygulama)
        $this->app->singleton(LockManager::class, CacheLockManager::class);

        // HMAC Authentication service
        $this->app->singleton(\App\Security\Contracts\HmacSigner::class, function ($app) {
            return new \App\Security\Hmac\Sha256Signer(
                config('security.hmac_secret'),
                config('security.hmac_ttl', 300)
            );
        });

        // IP Allowlist service
        $this->app->singleton(\App\Security\Contracts\Allowlist::class, function ($app) {
            return new \App\Security\Network\IpAllowlist(
                config('security.allowlist.cidrs', []),
                config('security.allowlist.mode', 'deny')
            );
        });



        // Consensus Repository binding
        $this->app->bind(
            \App\Domain\Consensus\ConsensusRepository::class,
            \App\Infrastructure\Consensus\EloquentConsensusRepository::class
        );

        // Test environment bindings
        if ($this->app->environment('testing')) {
            $this->app->bind(
                \App\Domain\Consensus\ConsensusRepository::class,
                \Tests\Fakes\FakeConsensusRepository::class
            );
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
