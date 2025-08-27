<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AI\AiProvider;
use App\Services\AI\ConsensusService;
use App\Services\AI\GeminiClient;
use App\Services\AI\GrokClient;
use App\Services\AI\OpenAIClient;
use App\Services\AI\PromptFactory;
use App\Services\Logger\AiLogCreatorService;
use Illuminate\Support\ServiceProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PromptFactory::class);
        $this->app->singleton(OpenAIClient::class);
        $this->app->singleton(GeminiClient::class);
        $this->app->singleton(GrokClient::class);
        $this->app->singleton(AiLogCreatorService::class);

        // AI Provider interface binding'leri
        $this->app->bind(AiProvider::class, OpenAIClient::class); // Default provider

        // Konsensüs: etkin sağlayıcıları sırayla bağla
        $this->app->bind(ConsensusService::class, function ($app) {
            $providers = [];
            foreach ([OpenAIClient::class, GeminiClient::class, GrokClient::class] as $cls) {
                $p = $app->make($cls);
                if ($p->enabled()) {
                    $providers[] = $p;
                }
            }

            return new ConsensusService($app->make(AiLogCreatorService::class), $providers);
        });
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        // Runtime GPT-4o enforcement for E2E validation compliance
        if (config('ai.model_enforcement.enabled')) {
            $this->enforceProductionModels();
        }
    }

    /**
     * Enforce GPT-4o model requirement at runtime
     */
    protected function enforceProductionModels(): void
    {
        // Override OpenAI model to gpt-4o for compliance
        config(['ai.providers.openai.model' => 'gpt-4o']);
        config(['ai.default_provider' => 'openai']);

        \Log::info('AI model enforcement activated', [
            'enforced_model' => 'gpt-4o',
            'reason' => 'E2E validation compliance',
            'override_env' => true,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
