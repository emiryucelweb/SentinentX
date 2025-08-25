<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Market\BybitMarketData;
use App\Services\WS\WsClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * WebSocket gap detection ve backfill reconciliation servisi
 *
 * Bu servis şu zinciri yönetir:
 * 1. WS Gap Detection: WebSocket data'sında eksik dönemleri tespit eder
 * 2. Backfill: Eksik data'yı REST API'den alır
 * 3. Reconcile: Data tutarlılığını kontrol eder ve düzeltir
 */
class GapReconciliationService
{
    private WsClient $wsClient;

    private BybitMarketData $marketData;

    /**
     * @var array<string, mixed>
     */
    private array $gapBuffer = [];

    private int $maxGapSeconds;

    private int $backfillBatchSize;

    public function __construct(
        WsClient $wsClient,
        BybitMarketData $marketData
    ) {
        $this->wsClient = $wsClient;
        $this->marketData = $marketData;
        $this->maxGapSeconds = config('trading.ws.max_gap_seconds', 30);
        $this->backfillBatchSize = config('trading.ws.backfill_batch_size', 100);
    }

    /**
     * WebSocket data stream'ini izler ve gap'leri tespit eder
     */
    public function monitorGaps(string $symbol): \Generator
    {
        $lastTimestamp = null;

        // Placeholder - implement when WsClient has subscribe method
        foreach ([] as $message) {
            $timestamp = $this->extractTimestamp($message);

            if ($lastTimestamp && $timestamp) {
                $gap = $timestamp - $lastTimestamp;

                if ($gap > $this->maxGapSeconds) {
                    $gapInfo = [
                        'symbol' => $symbol,
                        'start_time' => $lastTimestamp,
                        'end_time' => $timestamp,
                        'gap_seconds' => $gap,
                        'detected_at' => time(),
                        'type' => $this->detectGapType($message),
                    ];

                    Log::warning('WS Gap detected', $gapInfo);
                    $this->gapBuffer[] = $gapInfo;

                    yield $gapInfo;
                }
            }

            $lastTimestamp = $timestamp;

            // Periyodik gap processing
            if (count($this->gapBuffer) >= 10) {
                $this->processGapBuffer();
            }
        }
    }

    /**
     * Tespit edilen gap'leri backfill eder
     */
    /**
     * @param array<int, array<string, mixed>> $gaps
     * @return array<string, mixed>
     */
    public function backfillGaps(array $gaps): array
    {
        $results = [];

        foreach ($gaps as $gap) {
            try {
                $backfillResult = $this->backfillGap($gap);
                $results[] = array_merge($gap, ['backfill_result' => $backfillResult]);

                Log::info('Gap backfilled successfully', [
                    'symbol' => $gap['symbol'],
                    'gap_seconds' => $gap['gap_seconds'],
                    'backfilled_records' => $backfillResult['record_count'],
                ]);
            } catch (\Exception $e) {
                Log::error('Gap backfill failed', [
                    'gap' => $gap,
                    'error' => $e->getMessage(),
                ]);

                $results[] = array_merge($gap, [
                    'backfill_result' => ['success' => false, 'error' => $e->getMessage()],
                ]);
            }
        }

        return $results;
    }

    /**
     * Tek bir gap'i backfill eder
     */
    /**
     * @param array<string, mixed> $gap
     * @return array<string, mixed>
     */
    private function backfillGap(array $gap): array
    {
        $symbol = $gap['symbol'];
        $startTime = $gap['start_time'];
        $endTime = $gap['end_time'];

        $backfilledData = [];

        // Kline data backfill
        if ($gap['type'] === 'kline' || $gap['type'] === 'mixed') {
            // Placeholder - implement when BybitMarketData has getKlines method
            $klines = [];

            $backfilledData['klines'] = $klines;
        }

        // Ticker data backfill
        if ($gap['type'] === 'ticker' || $gap['type'] === 'mixed') {
            // Ticker için historical data yoksa current ticker alınabilir
            // Placeholder - implement when BybitMarketData has getTicker method
            $ticker = [];
            $backfilledData['ticker'] = $ticker;
        }

        // Orderbook data backfill (genelde snapshot alınır)
        if ($gap['type'] === 'orderbook' || $gap['type'] === 'mixed') {
            // Placeholder - implement when BybitMarketData has getOrderbook method
            $orderbook = [];
            $backfilledData['orderbook'] = $orderbook;
        }

        // Cache'e kaydet
        $cacheKey = "backfill_{$symbol}_{$startTime}_{$endTime}";
        Cache::put($cacheKey, $backfilledData, 3600); // 1 hour

        return [
            'success' => true,
            'record_count' => array_sum(array_map('count', $backfilledData)),
            'data_types' => array_keys($backfilledData),
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Data reconciliation - WS ve REST data'sını karşılaştırır
     */
    public function reconcileData(string $symbol, int $startTime, int $endTime): array
    {
        // WS data'sını al
        $wsData = $this->getWsData($symbol, $startTime, $endTime);

        // REST data'sını al
        $restData = $this->getRestData($symbol, $startTime, $endTime);

        // Reconciliation yapı
        $reconciliation = [
            'symbol' => $symbol,
            'period' => ['start' => $startTime, 'end' => $endTime],
            'ws_records' => count($wsData),
            'rest_records' => count($restData),
            'discrepancies' => [],
            'missing_in_ws' => [],
            'missing_in_rest' => [],
            'price_differences' => [],
        ];

        // Karşılaştırma logic'i
        foreach ($restData as $restRecord) {
            $timestamp = $restRecord['timestamp'] ?? $restRecord['time'];
            $wsRecord = $this->findWsRecord($wsData, $timestamp);

            if (! $wsRecord) {
                $reconciliation['missing_in_ws'][] = $restRecord;

                continue;
            }

            // Price comparison
            $priceDiff = $this->comparePrices($wsRecord, $restRecord);
            if ($priceDiff['significant']) {
                $reconciliation['price_differences'][] = $priceDiff;
            }
        }

        // WS'de olup REST'te olmayan kayıtlar
        foreach ($wsData as $wsRecord) {
            $timestamp = $wsRecord['timestamp'] ?? $wsRecord['time'];
            $restRecord = $this->findRestRecord($restData, $timestamp);

            if (! $restRecord) {
                $reconciliation['missing_in_rest'][] = $wsRecord;
            }
        }

        $reconciliation['accuracy_score'] = $this->calculateAccuracyScore($reconciliation);

        Log::info('Data reconciliation completed', [
            'symbol' => $symbol,
            'accuracy_score' => $reconciliation['accuracy_score'],
            'discrepancies_count' => count($reconciliation['discrepancies']),
        ]);

        return $reconciliation;
    }

    /**
     * Gap buffer'ını işler
     */
    private function processGapBuffer(): void
    {
        if (empty($this->gapBuffer)) {
            return;
        }

        $gaps = $this->gapBuffer;
        $this->gapBuffer = [];

        // Background job olarak backfill başlat
        dispatch(function () use ($gaps) {
            $this->backfillGaps($gaps);
        })->onQueue('backfill');
    }

    /**
     * Message'dan timestamp çıkarır
     */
    private function extractTimestamp(array $message): ?int
    {
        return $message['timestamp'] ?? $message['time'] ?? $message['T'] ?? null;
    }

    /**
     * Gap tipini tespit eder
     */
    private function detectGapType(array $message): string
    {
        if (isset($message['kline'])) {
            return 'kline';
        }
        if (isset($message['ticker'])) {
            return 'ticker';
        }
        if (isset($message['orderbook'])) {
            return 'orderbook';
        }

        return 'mixed';
    }

    /**
     * WS data'sını alır (cache'den veya memory'den)
     */
    private function getWsData(string $symbol, int $startTime, int $endTime): array
    {
        $cacheKey = "ws_data_{$symbol}_{$startTime}_{$endTime}";

        return Cache::get($cacheKey, []);
    }

    /**
     * REST data'sını alır
     */
    private function getRestData(string $symbol, int $startTime, int $endTime): array
    {
        return $this->marketData->getKlines(
            $symbol,
            '1m',
            limit: 1000,
            startTime: $startTime * 1000,
            endTime: $endTime * 1000
        );
    }

    /**
     * WS record'u bulur
     */
    private function findWsRecord(array $wsData, int $timestamp): ?array
    {
        foreach ($wsData as $record) {
            $recordTime = $record['timestamp'] ?? $record['time'] ?? 0;
            if (abs($recordTime - $timestamp) < 60) { // 1 minute tolerance
                return $record;
            }
        }

        return null;
    }

    /**
     * REST record'u bulur
     */
    private function findRestRecord(array $restData, int $timestamp): ?array
    {
        foreach ($restData as $record) {
            $recordTime = $record['timestamp'] ?? $record['time'] ?? 0;
            if (abs($recordTime - $timestamp) < 60) { // 1 minute tolerance
                return $record;
            }
        }

        return null;
    }

    /**
     * Price'ları karşılaştırır
     */
    private function comparePrices(array $wsRecord, array $restRecord): array
    {
        $wsPrice = (float) ($wsRecord['price'] ?? $wsRecord['close'] ?? 0);
        $restPrice = (float) ($restRecord['price'] ?? $restRecord['close'] ?? 0);

        $difference = abs($wsPrice - $restPrice);
        $percentageDiff = $restPrice > 0 ? ($difference / $restPrice) * 100 : 0;

        return [
            'ws_price' => $wsPrice,
            'rest_price' => $restPrice,
            'difference' => $difference,
            'percentage_diff' => $percentageDiff,
            'significant' => $percentageDiff > 0.01, // 0.01% threshold
        ];
    }

    /**
     * Accuracy score hesaplar
     */
    private function calculateAccuracyScore(array $reconciliation): float
    {
        $totalRecords = max($reconciliation['ws_records'], $reconciliation['rest_records']);
        if ($totalRecords === 0) {
            return 100.0;
        }

        $discrepancies = count($reconciliation['discrepancies']) +
                        count($reconciliation['missing_in_ws']) +
                        count($reconciliation['missing_in_rest']) +
                        count($reconciliation['price_differences']);

        return max(0, 100 - (($discrepancies / $totalRecords) * 100));
    }

    /**
     * Reconciliation raporu oluşturur
     */
    public function generateReconciliationReport(array $symbols, int $hours = 24): array
    {
        $endTime = time();
        $startTime = $endTime - ($hours * 3600);

        $report = [
            'generated_at' => Carbon::now()->toISOString(),
            'period_hours' => $hours,
            'symbols' => [],
            'summary' => [
                'total_symbols' => count($symbols),
                'avg_accuracy' => 0,
                'total_gaps' => 0,
                'total_backfills' => 0,
            ],
        ];

        $totalAccuracy = 0;

        foreach ($symbols as $symbol) {
            $reconciliation = $this->reconcileData($symbol, $startTime, $endTime);
            $report['symbols'][$symbol] = $reconciliation;

            $totalAccuracy += $reconciliation['accuracy_score'];
            $report['summary']['total_gaps'] += count($reconciliation['discrepancies']);
        }

        $report['summary']['avg_accuracy'] = count($symbols) > 0 ? $totalAccuracy / count($symbols) : 0;

        return $report;
    }
}
