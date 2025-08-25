<?php

declare(strict_types=1);

namespace App\DTO;

final class AiDecision
{
    public const ACTION_LONG = 'LONG';

    public const ACTION_SHORT = 'SHORT';

    public const ACTION_HOLD = 'HOLD';

    public const ACTION_CLOSE = 'CLOSE';

    public const ACTION_NO_TRADE = 'NO_TRADE';

    public const ACTION_NO_OPEN = 'NO_OPEN';

    /** @var string[] */
    private const ALLOWED = [
        self::ACTION_LONG,
        self::ACTION_SHORT,
        self::ACTION_HOLD,
        self::ACTION_CLOSE,
        self::ACTION_NO_TRADE,
        self::ACTION_NO_OPEN,
    ];

    public string $action;            // LONG|SHORT|HOLD|CLOSE|NO_TRADE|NO_OPEN

    public int $confidence;           // 0..100

    public ?float $stopLoss;          // null => provider/stop calc belirler

    public ?float $takeProfit;        // null => provider/stop calc belirler

    public ?float $qtyDeltaFactor;    // -1..+1 (null => 1.0 kabul)

    public string $reason;            // açıklama

    public ?array $raw;               // sağlayıcıya özgü ham veri (örn. leverage)

    public function __construct(
        string $action,
        int $confidence,
        ?float $stopLoss = null,
        ?float $takeProfit = null,
        ?float $qtyDeltaFactor = null,
        string $reason = '',
        ?array $raw = null
    ) {
        $action = strtoupper($action);
        if (! in_array($action, self::ALLOWED, true)) {
            throw new \InvalidArgumentException("Geçersiz action: {$action}");
        }
        if ($confidence < 0 || $confidence > 100) {
            throw new \InvalidArgumentException('confidence 0..100');
        }
        if ($qtyDeltaFactor !== null && ($qtyDeltaFactor < -1.0 || $qtyDeltaFactor > 1.0)) {
            throw new \InvalidArgumentException('qtyDeltaFactor -1..1');
        }

        $this->action = $action;
        $this->confidence = $confidence;
        $this->stopLoss = $stopLoss;
        $this->takeProfit = $takeProfit;
        $this->qtyDeltaFactor = $qtyDeltaFactor;
        $this->reason = $reason;
        $this->raw = $raw;
    }

    public static function fromArray(array $a): self
    {
        $rawAction = strtoupper((string) ($a['action'] ?? 'HOLD'));

        // Evrensel action normalizasyonu: Tüm AI provider'lar için standart format
        $normalizedAction = self::normalizeAction($rawAction);

        return new self(
            action: $normalizedAction,
            confidence: (int) ($a['confidence'] ?? 0),
            stopLoss: isset($a['stop_loss']) ? (float) $a['stop_loss'] : null,
            takeProfit: isset($a['take_profit']) ? (float) $a['take_profit'] : null,
            qtyDeltaFactor: isset($a['qty_delta_factor']) ? (float) $a['qty_delta_factor'] : null,
            reason: (string) ($a['reason'] ?? ''),
            raw: $a['raw'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'confidence' => $this->confidence,
            'stop_loss' => $this->stopLoss,
            'take_profit' => $this->takeProfit,
            'qty_delta_factor' => $this->qtyDeltaFactor,
            'reason' => $this->reason,
            'raw' => $this->raw,
        ];
    }

    public function isOpenIntent(): bool
    {
        return $this->action === self::ACTION_LONG || $this->action === self::ACTION_SHORT;
    }

    public function isNoTrade(): bool
    {
        return $this->action === self::ACTION_NO_TRADE || $this->action === self::ACTION_NO_OPEN;
    }

    /** Evrensel action normalizasyonu: Tüm AI provider'lar için standart format */
    private static function normalizeAction(string $action): string
    {
        $upperAction = strtoupper(trim($action));

        return match ($upperAction) {
            // Standart trading actions
            'LONG', 'SHORT', 'HOLD', 'CLOSE', 'NO_TRADE', 'NO_OPEN' => $upperAction,

            // AI provider specific actions
            'BUY', 'BUY_LONG', 'LONG_BUY', 'GO_LONG', 'LONG_POSITION' => 'LONG',
            'SELL', 'SELL_SHORT', 'SHORT_SELL', 'GO_SHORT', 'SHORT_POSITION' => 'SHORT',
            'WAIT', 'PASS', 'SKIP', 'STAY_PUT', 'NEUTRAL' => 'HOLD',
            'EXIT', 'CLOSE_POSITION', 'LIQUIDATE', 'CLOSE_ALL' => 'CLOSE',
            'NO_ACTION', 'STAY', 'ABSTAIN', 'AVOID' => 'NO_TRADE',

            // Fallback
            default => 'HOLD'
        };
    }
}
