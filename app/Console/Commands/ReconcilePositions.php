<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Trading\ReconciliationService;
use Illuminate\Console\Command;

final class ReconcilePositions extends Command
{
    protected $signature = 'sentx:reconcile-positions {--exchange-json=}';

    protected $description = 'Borsa pozisyonlarıyla yerel trades tablosunu mutabakat eder '
        .'(yetim pozisyonları düzeltir).';

    public function handle(ReconciliationService $svc): int
    {
        $jsonPath = (string) ($this->option('exchange-json') ?? '');
        $exchangePositions = [];
        $exchangeOrders = [];

        if ($jsonPath !== '' && is_file($jsonPath)) {
            $raw = file_get_contents($jsonPath);
            $data = json_decode($raw ?: '[]', true);
            $exchangePositions = is_array($data['positions'] ?? null) ? $data['positions'] : [];
            $exchangeOrders = is_array($data['orders'] ?? null) ? $data['orders'] : [];
        }

        $out = $svc->reconcile($exchangePositions, $exchangeOrders);
        $this->info(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
