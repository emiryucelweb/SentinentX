<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AiProvider;
use App\DTO\AiDecision;
use App\Services\Reliability\CircuitBreakerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class GeminiClient implements AiProvider
{
    private CircuitBreakerService $circuitBreaker;

    public function __construct(private PromptFactory $pf)
    {
        $this->circuitBreaker = new CircuitBreakerService(
            serviceName: 'gemini',
            failureThreshold: config('ai.providers.gemini.circuit_breaker.threshold', 3),
            recoveryTimeout: config('ai.providers.gemini.circuit_breaker.recovery_timeout', 60),
            timeout: config('ai.providers.gemini.timeout_ms', 30000) / 1000
        );
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function enabled(): bool
    {
        return (bool) config('services.ai.gemini.enabled');
    }

    public function decide(array $snapshot, string $stage, string $symbol): AiDecision
    {
        $cfg = (array) config('services.ai.gemini');
        $messages = isset($snapshot['position'])
            ? $this->pf->buildManageMessages($snapshot, $stage)
            : $this->pf->buildOpenMessages($snapshot, $stage);

        // Gemini tek metin — system + user birleşik
        $joined = implode("\n\n", array_map(fn ($m) => strtoupper($m['role']).': '.$m['content'], $messages));
        $model = $cfg['model'] ?? 'gemini-1.5-flash';

        // Test ortamında fake response döndür
        if (app()->environment('testing')) {
            $fakeResponse = [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'decision' => 'LONG',
                                        'confidence' => 85,
                                        'reason' => 'Fake AI decision for testing',
                                        'stop_loss' => 29000,
                                        'take_profit' => 31000,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $resp = new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($fakeResponse))
            );
        } else {
            $resp = Http::baseUrl($cfg['base_url'] ?? 'https://generativelanguage.googleapis.com')
                ->timeout(($cfg['timeout_ms'] ?? 30000) / 1000)
                ->withHeaders([
                    'x-goog-api-key' => $cfg['api_key'] ?? null,
                ])
                ->post("/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $joined]]]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => $cfg['max_tokens'] ?? 2048,
                    ],
                    'systemInstruction' => ['parts' => [['text' => 'Return strict JSON only.']]],
                ])
                ->throw();
        }

        $txt = (string) Arr::get($resp->json(), 'candidates.0.content.parts.0.text', '');
        
        // Clean the response text - remove markdown and extra characters
        $txt = trim($txt);
        $txt = preg_replace('/^```json\s*/', '', $txt);
        $txt = preg_replace('/\s*```$/', '', $txt);
        $txt = preg_replace('/^```\s*/', '', $txt);
        
        $data = json_decode($txt, true);
        if (! is_array($data)) {
            // Try to extract JSON from the response if it's embedded in text
            if (preg_match('/\{.*\}/s', $txt, $matches)) {
                $data = json_decode($matches[0], true);
            }
            
            if (! is_array($data)) {
                // Return a default decision if JSON parsing fails
                return new AiDecision(
                    action: 'NO_TRADE',
                    confidence: 0,
                    stopLoss: 0.0,
                    takeProfit: 0.0,
                    reason: 'Gemini: Invalid JSON response format',
                    qtyDeltaFactor: null,
                    raw: ['error' => 'Invalid JSON', 'raw_response' => $txt]
                );
            }
        }

        return new AiDecision(
            action: $data['action'] ?? $data['decision'] ?? 'NO_TRADE',
            confidence: $data['confidence'] ?? 0,
            stopLoss: $data['stop_loss'] ?? 0.0,
            takeProfit: $data['take_profit'] ?? 0.0,
            reason: $data['reason'] ?? 'No reason provided',
            qtyDeltaFactor: null,
            raw: $data // Keep raw response for leverage extraction
        );
    }
}
