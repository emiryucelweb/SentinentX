<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LabRun;
use App\Models\LabTrade;
use App\Services\AI\ConsensusService;
use App\Services\Exchange\BybitClient;
use App\Services\Lab\ExecutionCostModel;
use App\Services\Lab\MetricsService;
use App\Services\Lab\PathSimulator;
use App\Services\Notifier\AlertDispatcher;
use App\Services\Risk\CorrelationService;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class LabRunCommand extends Command
{
    protected $signature = 'sentx:lab-run 
        {--days=15 : Test süresi (gün)}
        {--symbols= : Trading symbols (e.g., BTC,ETH,SOL,XRP)}
        {--equity= : Başlangıç equity}
        {--risk-pct= : Trade başına risk yüzdesi}
        {--max-leverage= : Maksimum leverage}
        {--dry : Dry run mode}';

    protected $description = '15 gün LAB testnet çalıştır - performans gözetimi';

    public function handle(
        MetricsService $metrics,
        PathSimulator $pathSim,
        ExecutionCostModel $costs,
        ConsensusService $consensus,
        BybitClient $bybit,
        StopCalculator $stops,
        PositionSizer $sizer,
        RiskGuard $risk,
        CorrelationService $correlation,
        FundingGuard $funding,
        AlertDispatcher $alerts
    ): int {
        $days = max(1, (int) $this->option('days'));
        $symbols = $this->option('symbols') ?: 'BTC,ETH,SOL,XRP';
        $symbolList = array_map('trim', explode(',', $symbols));
        $initialEquity = (float) ($this->option('equity') ?: 10000.0);
        $riskPct = (float) ($this->option('risk-pct') ?: 1.0);
        $maxLeverage = (int) ($this->option('max-leverage') ?: 20);
        $isDryRun = (bool) $this->option('dry');

        if ($isDryRun) {
            $this->info('🔍 LAB RUN DRY MODE - No actual trades will be executed');
        }

        $this->info("🚀 Starting LAB run for {$days} days with symbols: ".implode(', ', $symbolList));
        $this->info('Initial equity: $'.number_format($initialEquity, 2));
        $this->line('');

        // LAB run kaydı oluştur
        $labRun = LabRun::create([
            'start_date' => now(),
            'end_date' => now()->addDays($days),
            'symbols' => json_encode($symbolList),
            'initial_equity' => $initialEquity,
            'risk_pct' => $riskPct,
            'max_leverage' => $maxLeverage,
            'status' => 'RUNNING',
            'meta' => json_encode([
                'dry_run' => $isDryRun,
                'command_options' => $this->options(),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $startDate = CarbonImmutable::now();
        $currentEquity = $initialEquity;
        $totalTrades = 0;
        $winningTrades = 0;
        $losingTrades = 0;

        // Günlük simülasyon döngüsü
        for ($day = 0; $day < $days; $day++) {
            $currentDate = $startDate->addDays($day);
            $this->info('📅 Day '.($day + 1)."/{$days}: ".$currentDate->toDateString());

            // Her sembol için günlük trading simülasyonu
            foreach ($symbolList as $symbol) {
                $dailyTrades = $this->simulateDailyTrading(
                    $symbol,
                    $currentDate,
                    $currentEquity,
                    $riskPct,
                    $maxLeverage,
                    $consensus,
                    $pathSim,
                    $costs,
                    $stops,
                    $sizer,
                    $risk,
                    $correlation,
                    $funding,
                    $isDryRun
                );

                foreach ($dailyTrades as $trade) {
                    $totalTrades++;
                    if ($trade['pnl_pct'] > 0) {
                        $winningTrades++;
                    } else {
                        $losingTrades++;
                    }

                    // Equity güncelle
                    $currentEquity *= (1.0 + $trade['pnl_pct'] / 100.0);
                }

                $this->line("  {$symbol}: ".count($dailyTrades).' trades, Equity: $'.number_format($currentEquity, 2));
            }

            // Günlük metrikleri hesapla ve kaydet
            $dailyMetrics = $metrics->computeDaily($currentDate, $initialEquity);
            $this->line('  📊 Daily PF: '.round($dailyMetrics['pf'], 4).
                       ', MaxDD: '.round($dailyMetrics['maxdd_pct'], 2).'%'.
                       ', Sharpe: '.($dailyMetrics['sharpe'] ? round($dailyMetrics['sharpe'], 3) : 'N/A'));

            // Günlük performans kontrolü
            if ($dailyMetrics['pf'] < 0.95) {
                $this->warn('  ⚠️  Daily performance below 0.95 threshold');
                $alerts->send('warn', 'LAB_DAILY_PERFORMANCE_LOW', 'Daily performance below threshold', [
                    'day' => $day + 1,
                    'pf' => $dailyMetrics['pf'],
                    'maxdd' => $dailyMetrics['maxdd_pct'],
                    'equity' => $currentEquity,
                ]);
            }

            $this->line('');
        }

        // LAB run'ı tamamla
        $labRun->update([
            'end_date' => now(),
            'status' => 'COMPLETED',
            'final_equity' => $currentEquity,
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'final_pf' => $currentEquity / $initialEquity,
            'meta' => json_encode([
                'final_metrics' => [
                    'total_trades' => $totalTrades,
                    'winning_trades' => $winningTrades,
                    'losing_trades' => $losingTrades,
                    'win_rate' => $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0,
                    'final_equity' => $currentEquity,
                    'total_return_pct' => (($currentEquity - $initialEquity) / $initialEquity) * 100,
                ],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        // Final rapor
        $this->info('🎯 LAB Run Completed!');
        $this->info('Final Equity: $'.number_format($currentEquity, 2));
        $this->info('Total Return: '.round((($currentEquity - $initialEquity) / $initialEquity) * 100, 2).'%');
        $this->info('Total Trades: '.$totalTrades);
        $this->info('Win Rate: '.round(($winningTrades / max(1, $totalTrades)) * 100, 1).'%');

        // Final alert
        $alerts->send('info', 'LAB_RUN_COMPLETED', 'LAB run completed', [
            'days' => $days,
            'symbols' => $symbolList,
            'initial_equity' => $initialEquity,
            'final_equity' => $currentEquity,
            'total_return_pct' => (($currentEquity - $initialEquity) / $initialEquity) * 100,
            'total_trades' => $totalTrades,
            'win_rate' => ($winningTrades / max(1, $totalTrades)) * 100,
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function simulateDailyTrading(
        string $symbol,
        CarbonImmutable $date,
        float $equity,
        float $riskPct,
        int $maxLeverage,
        ConsensusService $consensus,
        PathSimulator $pathSim,
        ExecutionCostModel $costs,
        StopCalculator $stops,
        PositionSizer $sizer,
        RiskGuard $risk,
        CorrelationService $correlation,
        FundingGuard $funding,
        bool $isDryRun
    ): array {
        $trades = [];
        $maxDailyTrades = 3; // Günlük maksimum trade sayısı

        for ($tradeNum = 0; $tradeNum < $maxDailyTrades; $tradeNum++) {
            // Market snapshot simülasyonu
            $price = $this->simulatePrice($symbol, $date);
            $atr = $price * 0.02; // %2 ATR simülasyonu

            // AI consensus simülasyonu
            $payload = [
                'symbol' => $symbol,
                'price' => $price,
                'atr' => $atr,
                'equity' => $equity,
                'cycle_id' => Str::uuid(),
            ];

            $decision = $consensus->decide($payload);
            $action = strtoupper($decision['final']['action'] ?? 'HOLD');
            $confidence = (int) ($decision['final']['confidence'] ?? 0);

            if (! in_array($action, ['LONG', 'SHORT']) || $confidence < 60) {
                continue; // Trade yapma
            }

            // Risk gate kontrolü
            $gateResult = $risk->allowOpenWithGuards(
                $symbol,
                $price,
                $action,
                $maxLeverage,
                $price * 0.95, // SL
                $funding,
                $correlation
            );

            if (! $gateResult['ok']) {
                continue; // Risk gate engelledi
            }

            // Pozisyon boyutlandırma
            $qty = $sizer->sizeByRisk($equity, $riskPct, $atr, $price, $maxLeverage, 0.001, 0.001);
            $qty = (float) ($qty['qty'] ?? 0.0);

            if ($qty <= 0) {
                continue;
            }

            // Stop/TP hesaplama
            $sl = $stops->atrStop($action, $price, $atr);
            $tp = $stops->atrTakeProfit($action, $price, $atr);

            // Path simülasyonu
            $bars = $this->generateSyntheticBars($price, $atr);
            $result = $pathSim->firstTouch($action, $price, $sl, $tp, $bars);

            // P&L hesaplama
            $exitPrice = (float) $result['exit'];
            $pnlPct = $this->calculatePnlPct($action, $price, $exitPrice);

            // Trade kaydı
            $trade = LabTrade::create([
                'symbol' => $symbol,
                'side' => $action,
                'qty' => $qty,
                'entry_price' => $price,
                'exit_price' => $exitPrice,
                'opened_at' => $date->toDateTime(),
                'closed_at' => $date->addMinutes($result['bars'] * 5)->toDateTime(),
                'pnl_pct' => round($pnlPct, 4),
                'cycle_uuid' => $payload['cycle_id'],
                'meta' => json_encode([
                    'atr' => $atr,
                    'sl' => $sl,
                    'tp' => $tp,
                    'bars_used' => $result['bars'],
                    'exit_reason' => $result['reason'],
                    'confidence' => $confidence,
                    'risk_pct' => $riskPct,
                    'leverage' => $maxLeverage,
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $trades[] = $trade->toArray();
        }

        return $trades;
    }

    private function simulatePrice(string $symbol, CarbonImmutable $date): float
    {
        // Basit fiyat simülasyonu - gerçek implementasyonda market data kullanılır
        $basePrice = match ($symbol) {
            'BTC' => 50000.0,
            'ETH' => 3000.0,
            'SOL' => 100.0,
            'XRP' => 0.5,
            default => 100.0,
        };

        $volatility = 0.02; // %2 günlük volatilite
        $drift = 0.001; // %0.1 günlük drift

        $randomFactor = (mt_rand(-100, 100) / 100.0) * $volatility;
        $timeFactor = $date->diffInDays(CarbonImmutable::now()) * $drift;

        return $basePrice * (1.0 + $randomFactor + $timeFactor);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSyntheticBars(float $price, float $atr): array
    {
        $bars = [];
        $currentPrice = $price;

        for ($i = 0; $i < 20; $i++) {
            $volatility = $atr / $price;
            $change = (mt_rand(-100, 100) / 100.0) * $volatility;
            $currentPrice *= (1.0 + $change);

            $high = $currentPrice * (1.0 + abs($change) * 0.5);
            $low = $currentPrice * (1.0 - abs($change) * 0.5);
            $open = $currentPrice * (1.0 + (mt_rand(-50, 50) / 100.0) * $volatility * 0.1);

            $bars[] = [
                'ts' => time() + ($i * 300), // 5 dakikalık barlar
                'o' => $open,
                'h' => $high,
                'l' => $low,
                'c' => $currentPrice,
            ];
        }

        return $bars;
    }

    private function calculatePnlPct(string $action, float $entry, float $exit): float
    {
        if ($action === 'LONG') {
            return (($exit - $entry) / $entry) * 100.0;
        } else {
            return (($entry - $exit) / $entry) * 100.0;
        }
    }
}
