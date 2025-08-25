<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Risk\CorrelationService;
use App\Services\Risk\FundingGuard;
use App\Services\Trading\ImCapService;
use Illuminate\Console\Command;

final class RiskAnalysisCommand extends Command
{
    protected $signature = 'sentx:risk-analysis 
        {--symbols= : Trading symbols (e.g., BTC,ETH,SOL,XRP)}
        {--equity= : Account equity}
        {--margin-utilization= : Current margin utilization percentage}
        {--free-collateral= : Free collateral amount}';

    protected $description = 'Analyze risk factors for trading symbols';

    public function handle(
        CorrelationService $correlation,
        FundingGuard $funding,
        ImCapService $imCap
    ): int {
        $symbols = $this->option('symbols') ?: 'BTC,ETH,SOL,XRP';
        $symbolList = array_map('trim', explode(',', $symbols));

        $equity = (float) ($this->option('equity') ?: 10000.0);
        $marginUtilization = (float) ($this->option('margin-utilization') ?: 25.0);
        $freeCollateral = (float) ($this->option('free-collateral') ?: 7500.0);

        $this->info('🔍 Risk Analysis for: '.implode(', ', $symbolList));
        $this->line('');

        // 1. IM Cap Analizi
        $this->info('📊 IM Cap Analysis:');
        $imCapData = $imCap->calculateImCap($equity, $marginUtilization, $freeCollateral);
        $this->line("  Risk Band: {$imCapData['band']}");
        $this->line('  IM Cap: $'.number_format($imCapData['im_cap'], 2));
        $this->line("  Leverage Range: {$imCapData['leverage_clamp'][0]}x - {$imCapData['leverage_clamp'][1]}x");
        $this->line("  Margin Utilization: {$imCapData['margin_utilization']}%");
        $this->line('');

        // 2. Korelasyon Analizi
        $this->info('🔗 Correlation Analysis:');
        if (count($symbolList) > 1) {
            $correlationMatrix = $correlation->matrix($symbolList, 30, '5');
            foreach ($symbolList as $symbol1) {
                foreach ($symbolList as $symbol2) {
                    if ($symbol1 !== $symbol2) {
                        $corr = $correlationMatrix[$symbol1][$symbol2] ?? 0.0;
                        $this->line("  {$symbol1} ↔ {$symbol2}: ".round($corr, 3));
                    }
                }
            }
        } else {
            $this->line('  Single symbol - no correlation analysis needed');
        }
        $this->line('');

        // 3. Beta Analizi
        $this->info('📈 Beta Analysis:');
        foreach ($symbolList as $symbol) {
            $beta = $correlation->calculateBeta($symbol, 30, '5');
            $this->line("  {$symbol}: ".round($beta, 3));
        }
        $this->line('');

        // 4. Funding Analizi
        $this->info('💰 Funding Analysis:');
        $fundingWindows = $funding->analyzeFundingWindows($symbolList);
        foreach ($fundingWindows['windows'] as $symbol => $data) {
            $this->line("  {$symbol}:");
            $this->line("    Time to funding: {$data['time_to_funding']} minutes");
            $this->line('    Optimal entry: '.($data['optimal_entry'] ? 'Yes' : 'No'));
            $this->line('    Funding rate: '.round($data['funding_rate'] * 100, 3).'%');
            $this->line("    Recommendation: {$data['recommendation']}");
        }
        $this->line('');

        // 5. Pozisyon Boyutlandırma Örneği
        $this->info('📏 Position Sizing Example:');
        $leverage = 10.0;
        $price = 50000.0; // BTC fiyatı örneği

        foreach ($symbolList as $symbol) {
            $positionData = $imCap->calculatePositionSize(
                $equity,
                $marginUtilization,
                $freeCollateral,
                $leverage,
                $price
            );

            // Beta adjustment
            $beta = $correlation->calculateBeta($symbol, 30, '5');
            $betaData = $imCap->calculateBetaAdjustedPosition($positionData['qty'], $beta);

            // Funding adjustment
            $fundingData = $funding->calculateFundingAdjustedPosition($betaData['qty'], $symbol);

            $this->line("  {$symbol}:");
            $this->line('    Base Qty: '.round($positionData['qty'], 6));
            $this->line('    Beta Adjusted: '.round($betaData['qty'], 6));
            $this->line('    Final Qty: '.round($fundingData['qty'], 6));
            $this->line('    Total Adjustment: '.round($fundingData['factor'] * $betaData['beta_factor'], 3));
        }
        $this->line('');

        // 6. Risk Önerileri
        $this->info('⚠️  Risk Recommendations:');
        foreach ($fundingWindows['recommendations'] as $rec) {
            $this->line("  • {$rec}");
        }

        if ($marginUtilization > 60) {
            $this->line("  • High margin utilization ({$marginUtilization}%) - consider reducing positions");
        }

        if ($marginUtilization < 20) {
            $this->line("  • Low margin utilization ({$marginUtilization}%) - room for more positions");
        }

        return self::SUCCESS;
    }
}
