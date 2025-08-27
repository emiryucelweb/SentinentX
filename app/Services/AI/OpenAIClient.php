<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AiProvider;
use App\DTO\AiDecision;
use App\Services\Reliability\CircuitBreakerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class OpenAIClient implements AiProvider
{
    private CircuitBreakerService $circuitBreaker;

    public function __construct(private PromptFactory $pf)
    {
        $this->circuitBreaker = new CircuitBreakerService(
            serviceName: 'openai',
            failureThreshold: config('ai.providers.openai.circuit_breaker.threshold', 3),
            recoveryTimeout: config('ai.providers.openai.circuit_breaker.recovery_timeout', 60),
            timeout: config('ai.providers.openai.timeout_ms', 30000) / 1000
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    public function enabled(): bool
    {
        return (bool) config('services.ai.openai.enabled');
    }

    public function decide(array $snapshot, string $stage, string $symbol): AiDecision
    {
        $cfg = (array) config('services.ai.openai');
        $messages = isset($snapshot['position'])
            ? $this->pf->buildManageMessages($snapshot, $stage)
            : $this->pf->buildOpenMessages($snapshot, $stage);

        $payload = [
            'model' => $cfg['model'] ?? 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => $cfg['max_tokens'] ?? 2048,
        ];

        // Test ortamında fake response döndür
        if (app()->environment('testing')) {
            $fakeResponse = [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'decision' => 'LONG',
                                'confidence' => 85,
                                'reason' => 'Fake OpenAI decision for testing',
                                'stop_loss' => 29000,
                                'take_profit' => 31000,
                            ]),
                        ],
                    ],
                ],
            ];
            $resp = new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($fakeResponse))
            );
        } else {
            // Use circuit breaker for external API call
            $resp = $this->circuitBreaker->call(function () use ($cfg, $payload) {
                return Http::baseUrl($cfg['base_url'] ?? 'https://api.openai.com/v1')
                    ->timeout(($cfg['timeout_ms'] ?? 30000) / 1000)
                    ->withToken($cfg['api_key'] ?? '')
                    ->post('chat/completions', $payload)
                    ->throw();
            });
        }

        $text = (string) Arr::get($resp->json(), 'choices.0.message.content', '');
        $data = json_decode($text, true);
        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI: geçersiz JSON çıktı');
        }

        return AiDecision::fromArray([
            'action' => $data['action'] ?? $data['decision'] ?? 'NO_TRADE',
            'confidence' => $data['confidence'] ?? 0,
            'stop_loss' => $data['stop_loss'] ?? 0.0,
            'take_profit' => $data['take_profit'] ?? 0.0,
            'reason' => $data['reason'] ?? 'No reason provided',
            'raw' => $data, // Keep raw response for leverage extraction
        ]);
    }
}
