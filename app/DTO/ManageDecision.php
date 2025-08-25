<?php

declare(strict_types=1);

namespace App\DTO;

final class ManageDecision
{
    /** @var 'HOLD'|'CLOSE' */
    public string $action;

    public int $confidence; // 0..100

    public ?float $newStopLoss;

    public ?float $newTakeProfit;

    public ?float $qtyDeltaFactor; // -1..+1

    public string $reason;

    public function __construct(
        string $action,
        int $confidence,
        ?float $newStopLoss = null,
        ?float $newTakeProfit = null,
        ?float $qtyDeltaFactor = null,
        string $reason = ''
    ) {
        $action = strtoupper($action);
        if (! in_array($action, ['HOLD', 'CLOSE'], true)) {
            throw new \InvalidArgumentException('ManageDecision action HOLD|CLOSE');
        }
        if ($confidence < 0 || $confidence > 100) {
            throw new \InvalidArgumentException('confidence 0..100');
        }
        if ($qtyDeltaFactor !== null && ($qtyDeltaFactor < -1.0 || $qtyDeltaFactor > 1.0)) {
            throw new \InvalidArgumentException('qtyDeltaFactor -1..1');
        }
        $this->action = $action;
        $this->confidence = $confidence;
        $this->newStopLoss = $newStopLoss;
        $this->newTakeProfit = $newTakeProfit;
        $this->qtyDeltaFactor = $qtyDeltaFactor;
        $this->reason = $reason;
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'confidence' => $this->confidence,
            'new_stop_loss' => $this->newStopLoss,
            'new_take_profit' => $this->newTakeProfit,
            'qty_delta_factor' => $this->qtyDeltaFactor,
            'reason' => $this->reason,
        ];
    }
}
