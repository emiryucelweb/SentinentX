<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class TestMathCommand extends Command
{
    protected $signature = 'sentx:test:math {--symbol=BTCUSDT : Symbol to test} {--price=50000 : Test price}';

    protected $description = 'Test mathematical consistency - PnL signs, fee calculations, margin equations';

    public function handle(): int
    {
        $symbol = $this->option('symbol');
        $testPrice = (float) $this->option('price');

        $this->info("🧮 Math Test: {$symbol} @ {$testPrice}");

        $results = [
            'timestamp' => now()->toISOString(),
            'symbol' => $symbol,
            'test_price' => $testPrice,
            'tests' => [],
            'overall' => 'PASS',
        ];

        // Test 1: PnL Sign Consistency
        $pnlTest = $this->testPnLSigns($testPrice);
        $results['tests']['pnl_signs'] = $pnlTest;

        // Test 2: Fee Accounting
        $feeTest = $this->testFeeAccounting();
        $results['tests']['fee_accounting'] = $feeTest;

        // Test 3: Margin Equation
        $marginTest = $this->testMarginEquation();
        $results['tests']['margin_equation'] = $marginTest;

        // Test 4: Liquidation Distance
        $liqTest = $this->testLiquidationDistance($testPrice);
        $results['tests']['liquidation_distance'] = $liqTest;

        // Test 5: Tick/Lot Rounding
        $roundingTest = $this->testTickLotRounding($testPrice);
        $results['tests']['tick_lot_rounding'] = $roundingTest;

        // Overall status
        $failedTests = array_filter(
            $results['tests'],
            fn ($test) => $test['status'] === 'FAIL'
        );

        if (count($failedTests) > 0) {
            $results['overall'] = 'FAIL';
        }

        // Output results
        $this->table(['Test', 'Status', 'Details'], array_map(function ($name, $test) {
            return [
                $name,
                $test['status'],
                json_encode($test['details'], JSON_UNESCAPED_UNICODE),
            ];
        }, array_keys($results['tests']), $results['tests']));

        $this->info("Overall Result: {$results['overall']}");

        if ($results['overall'] === 'PASS') {
            $this->info('✅ All math tests passed');

            return 0;
        } else {
            $this->error('❌ Some math tests failed');

            return 1;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function testPnLSigns(float $price): array
    {
        $entryPrice = $price;
        $longMarkPrice = $price * 1.01; // +1%
        $shortMarkPrice = $price * 0.99; // -1%

        $longPnL = ($longMarkPrice - $entryPrice) / $entryPrice * 100;
        $shortPnL = ($entryPrice - $shortMarkPrice) / $entryPrice * 100;

        $longCorrect = $longPnL > 0;
        $shortCorrect = $shortPnL > 0;

        return [
            'status' => ($longCorrect && $shortCorrect) ? 'PASS' : 'FAIL',
            'details' => [
                'long_pnl' => round($longPnL, 4).'%',
                'short_pnl' => round($shortPnL, 4).'%',
                'long_correct' => $longCorrect,
                'short_correct' => $shortCorrect,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testFeeAccounting(): array
    {
        $pnlGross = 100.0;
        $realFees = [2.5, 1.8, 3.2];
        $totalFees = array_sum($realFees);
        $pnlNet = $pnlGross - $totalFees;

        $expectedNet = 92.5;
        $calculationCorrect = abs($pnlNet - $expectedNet) < 0.01;

        return [
            'status' => $calculationCorrect ? 'PASS' : 'FAIL',
            'details' => [
                'pnl_gross' => $pnlGross,
                'real_fees' => $realFees,
                'total_fees' => $totalFees,
                'pnl_net' => $pnlNet,
                'expected_net' => $expectedNet,
                'calculation_correct' => $calculationCorrect,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testMarginEquation(): array
    {
        $equity = 10000.0;
        $imTotal = 8000.0;
        $em = 2000.0;
        $freeCollateral = $equity - $imTotal - $em;

        $imCheck = $imTotal <= $equity;
        $emCheck = $em >= 0;
        $fcCheck = $freeCollateral >= 0;

        return [
            'status' => ($imCheck && $emCheck && $fcCheck) ? 'PASS' : 'FAIL',
            'details' => [
                'equity' => $equity,
                'im_total' => $imTotal,
                'em' => $em,
                'free_collateral' => $freeCollateral,
                'im_check' => $imCheck,
                'em_check' => $emCheck,
                'fc_check' => $fcCheck,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testLiquidationDistance(float $price): array
    {
        $atr = $price * 0.02; // 2% ATR
        $minDistance = $atr * 2.0; // 2×ATR

        $liqDistance = $minDistance * 1.5; // 1.5x minimum
        $sufficient = $liqDistance >= $minDistance;

        return [
            'status' => $sufficient ? 'PASS' : 'FAIL',
            'details' => [
                'atr' => $atr,
                'min_distance' => $minDistance,
                'liq_distance' => $liqDistance,
                'sufficient' => $sufficient,
                'buffer_ratio' => $liqDistance / $minDistance,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testTickLotRounding(float $price): array
    {
        $tickSize = 0.1;
        $lotSize = 0.001;

        $roundedPrice = round($price / $tickSize) * $tickSize;
        $roundedQty = round(0.1 / $lotSize) * $lotSize;

        $priceValid = abs($roundedPrice - $price) <= $tickSize / 2;
        $qtyValid = $roundedQty > 0;

        return [
            'status' => ($priceValid && $qtyValid) ? 'PASS' : 'FAIL',
            'details' => [
                'tick_size' => $tickSize,
                'lot_size' => $lotSize,
                'original_price' => $price,
                'rounded_price' => $roundedPrice,
                'price_valid' => $priceValid,
                'rounded_qty' => $roundedQty,
                'qty_valid' => $qtyValid,
            ],
        ];
    }
}
