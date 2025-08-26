<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Contracts\Notifier\AlertDispatcher;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

final class AlertService
{
    public function __construct(
        private AlertDispatcher $alertDispatcher
    ) {}

    public function createAlert(
        string $type,
        string $message,
        string $severity = 'info',
        array $context = []
    ): Alert {
        $alert = Alert::create([
            'type' => $type,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'status' => 'active',
        ]);

        // Alert created successfully - dispatching handled elsewhere if needed

        Log::log($this->getLogLevel($severity), "Alert created: {$type}", [
            'alert_id' => $alert->id,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
        ]);

        return $alert;
    }

    public function createSystemAlert(
        string $component,
        string $message,
        string $severity = 'warning'
    ): Alert {
        return $this->createAlert(
            "system.{$component}",
            $message,
            $severity,
            ['component' => $component, 'timestamp' => now()->toISOString()]
        );
    }

    public function createRiskAlert(
        string $riskType,
        string $message,
        array $metrics = []
    ): Alert {
        return $this->createAlert(
            "risk.{$riskType}",
            $message,
            'warning',
            ['risk_type' => $riskType, 'metrics' => $metrics, 'timestamp' => now()->toISOString()]
        );
    }

    public function createTradingAlert(
        string $symbol,
        string $action,
        string $message,
        array $tradeData = []
    ): Alert {
        return $this->createAlert(
            "trading.{$action}",
            $message,
            'info',
            ['symbol' => $symbol, 'action' => $action, 'trade_data' => $tradeData, 'timestamp' => now()->toISOString()]
        );
    }

    public function acknowledgeAlert(int $alertId, int $userId): bool
    {
        $alert = Alert::find($alertId);
        if (! $alert) {
            return false;
        }

        $alert->update([
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'status' => 'acknowledged',
        ]);

        return true;
    }

    public function resolveAlert(int $alertId, int $userId, string $resolution = ''): bool
    {
        $alert = Alert::find($alertId);
        if (! $alert) {
            return false;
        }

        $alert->update([
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution' => $resolution,
            'status' => 'resolved',
        ]);

        return true;
    }

    public function getActiveAlerts(?string $type = null, ?string $severity = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Alert::where('status', 'active');

        if ($type) {
            $query->where('type', 'like', "{$type}%");
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function getLogLevel(string $severity): string
    {
        return match ($severity) {
            'critical' => 'critical',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'info'
        };
    }
}
