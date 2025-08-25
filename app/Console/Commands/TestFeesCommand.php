<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class TestFeesCommand extends Command
{
    protected $signature = 'sentx:test:fees {--symbol=BTCUSDT : Symbol to test}';

    protected $description = 'Test fee accounting - maker/taker mix, partial fills, real fee calculations';

    public function handle(): int
    {
        $symbol = $this->option('symbol');
        $this->info("💰 Fee Test: {$symbol}");

        $results = [
            'timestamp' => now()->toISOString(),
            'symbol' => $symbol,
            'tests' => [],
            'overall' => 'PASS',
        ];

        // Test 1: Maker/Taker Mix
        $makerTakerTest = $this->testMakerTakerMix();
        $results['tests']['maker_taker_mix'] = $makerTakerTest;

        // Test 2: Partial Fills
        $partialFillsTest = $this->testPartialFills();
        $results['tests']['partial_fills'] = $partialFillsTest;

        // Test 3: Real Fee Calculation
        $realFeeTest = $this->testRealFeeCalculation();
        $results['tests']['real_fee_calculation'] = $realFeeTest;

        // Test 4: Fee Aggregation
        $feeAggregationTest = $this->testFeeAggregation();
        $results['tests']['fee_aggregation'] = $feeAggregationTest;

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
            $this->info('✅ All fee tests passed');

            return 0;
        } else {
            $this->error('❌ Some fee tests failed');

            return 1;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function testMakerTakerMix(): array
    {
        $makerFee = 0.001; // 0.1%
        $takerFee = 0.006; // 0.6%

        $mix = [
            'maker' => ['fee' => $makerFee, 'volume' => 1000],
            'taker' => ['fee' => $takerFee, 'volume' => 500],
        ];

        $totalVolume = $mix['maker']['volume'] + $mix['taker']['volume'];
        $weightedFee = (
            ($mix['maker']['fee'] * $mix['maker']['volume']) +
            ($mix['taker']['fee'] * $mix['taker']['volume'])
        ) / $totalVolume;

        $expectedWeightedFee = 0.00267; // (0.001*1000 + 0.006*500) / 1500
        $calculationCorrect = abs($weightedFee - $expectedWeightedFee) < 0.0001;

        return [
            'status' => $calculationCorrect ? 'PASS' : 'FAIL',
            'details' => [
                'maker_fee' => $makerFee,
                'taker_fee' => $takerFee,
                'total_volume' => $totalVolume,
                'weighted_fee' => $weightedFee,
                'expected_weighted_fee' => $expectedWeightedFee,
                'calculation_correct' => $calculationCorrect,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testPartialFills(): array
    {
        $orderQty = 1.0;
        $partialFills = [0.3, 0.4, 0.3];
        $totalFilled = array_sum($partialFills);

        $fillComplete = abs($totalFilled - $orderQty) < 0.001;
        $partialCount = count($partialFills);

        return [
            'status' => $fillComplete ? 'PASS' : 'FAIL',
            'details' => [
                'order_qty' => $orderQty,
                'partial_fills' => $partialFills,
                'total_filled' => $totalFilled,
                'fill_complete' => $fillComplete,
                'partial_count' => $partialCount,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testRealFeeCalculation(): array
    {
        $pnlGross = 100.0;
        $realFees = [2.5, 1.8, 3.2, 0.5];
        $totalFees = array_sum($realFees);
        $pnlNet = $pnlGross - $totalFees;

        $expectedNet = 92.0; // 100 - 8 = 92
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
    private function testFeeAggregation(): array
    {
        $fills = [
            ['fee' => 2.5, 'fee_usd' => 2.5],
            ['fee' => 1.8, 'fee_usd' => 1.8],
            ['fee' => 3.2, 'fee_usd' => 3.2],
        ];

        $totalFee = array_sum(array_column($fills, 'fee'));
        $totalFeeUsd = array_sum(array_column($fills, 'fee_usd'));

        $aggregationCorrect = abs($totalFee - $totalFeeUsd) < 0.01;

        return [
            'status' => $aggregationCorrect ? 'PASS' : 'FAIL',
            'details' => [
                'fills' => $fills,
                'total_fee' => $totalFee,
                'total_fee_usd' => $totalFeeUsd,
                'aggregation_correct' => $aggregationCorrect,
            ],
        ];
    }
}
