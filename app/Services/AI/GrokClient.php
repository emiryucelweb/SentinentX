<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AiProvider;
use App\DTO\AiDecision;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class GrokClient implements AiProvider
{
    public function __construct(private PromptFactory $pf) {}

    public function name(): string
    {
        return 'grok';
    }

    public function enabled(): bool
    {
        return (bool) config('services.ai.grok.enabled');
    }

    public function decide(array $snapshot, string $stage, string $symbol): AiDecision
    {
        $cfg = (array) config('services.ai.grok');
        $messages = isset($snapshot['position'])
            ? $this->pf->buildManageMessages($snapshot, $stage)
            : $this->pf->buildOpenMessages($snapshot, $stage);

        $payload = [
            'model' => $cfg['model'] ?? 'grok-4-0709',
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
                                'reason' => 'Fake Grok decision for testing',
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
            $resp = Http::baseUrl($cfg['base_url'] ?? 'https://api.x.ai/v1')
                ->timeout(($cfg['timeout_ms'] ?? 30000) / 1000)
                ->withToken($cfg['api_key'] ?? '')
                ->post('chat/completions', $payload)
                ->throw();
        }

        $text = (string) Arr::get($resp->json(), 'choices.0.message.content', '');
        $data = json_decode($text, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Grok: geçersiz JSON çıktı');
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
