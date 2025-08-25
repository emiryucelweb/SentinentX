<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\AiProvider;
use App\DTO\AiDecision;

class FakeAiProvider implements AiProvider
{
    private string $name;

    private array $response;

    public function __construct(string $name, array $response)
    {
        $this->name = $name;
        $this->response = $response;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function decide(array $payload, string $stage = 'STAGE1', ?string $symbol = null): AiDecision
    {
        return AiDecision::fromArray([
            'action' => $this->response['action'] ?? 'NONE',
            'confidence' => $this->response['confidence'] ?? 50,
            'stopLoss' => $this->response['stopLoss'] ?? null,
            'takeProfit' => $this->response['takeProfit'] ?? null,
            'qtyDeltaFactor' => $this->response['qtyDeltaFactor'] ?? null,
            'reason' => $this->response['reason'] ?? 'Test response',
            'raw' => [
                'leverage' => $this->response['leverage'] ?? 1,
                'lev' => $this->response['leverage'] ?? 1,
                'take_profit' => $this->response['takeProfit'] ?? null,
                'stop_loss' => $this->response['stopLoss'] ?? null,
            ],
        ]);
    }

    public function enabled(): bool
    {
        return true;
    }

    public function isHealthy(): bool
    {
        return true;
    }

    public function getLastError(): ?string
    {
        return null;
    }

    public function getMetrics(): array
    {
        return [
            'requests_total' => 1,
            'requests_successful' => 1,
            'requests_failed' => 0,
            'average_response_time_ms' => 100,
            'last_request_at' => now()->toISOString(),
        ];
    }
}
