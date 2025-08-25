<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Contracts\Trading\PnlServiceInterface;
use Illuminate\Support\Facades\Log;

final class DriftGuardService
{
    public function __construct(
        private ExchangeClientInterface $exchange,
        private PnlServiceInterface $pnlService,
        private float $maxDriftThreshold = 5.0, // %5 default
        private float $maxPositionSizeThreshold = 20.0, // %20 default
        private int $driftCheckInterval = 300 // 5 dakika default
    ) {}

    /**
     * Pozisyon drift'ini kontrol et
     *
     * @param  string  $symbol  Sembol
     * @param  string  $side  LONG/SHORT
     * @param  float  $entryPrice  Giriş fiyatı
     * @param  float  $currentPrice  Mevcut fiyat
     * @param  float  $qty  Pozisyon miktarı
     * @param  float  $equity  Toplam equity
     * @param  array  $opts  Ek options
     * @return array ['ok' => bool, 'drift_pct' => float, 'risk_level' => string, 'actions' => array]
     */
    /**
     * @param array<string, mixed> $opts
     * @return array<string, mixed>
     */
    public function checkPositionDrift(
        string $symbol,
        string $side,
        float $entryPrice,
        float $currentPrice,
        float $qty,
        float $equity,
        array $opts = []
    ): array {
        // Drift yüzdesini hesapla
        $driftPct = $this->calculateDriftPercentage($entryPrice, $currentPrice, $side);

        // Pozisyon büyüklüğünü kontrol et
        $positionSizePct = $this->calculatePositionSizePercentage($qty, $currentPrice, $equity);

        // Risk seviyesini belirle
        $riskLevel = $this->determineRiskLevel($driftPct, $positionSizePct);

        // Gerekli aksiyonları belirle
        $actions = $this->determineActions($riskLevel, $driftPct, $positionSizePct, $opts);

        $result = [
            'ok' => $riskLevel !== 'CRITICAL',
            'drift_pct' => $driftPct,
            'position_size_pct' => $positionSizePct,
            'risk_level' => $riskLevel,
            'actions' => $actions,
            'details' => [
                'symbol' => $symbol,
                'side' => $side,
                'entry_price' => $entryPrice,
                'current_price' => $currentPrice,
                'qty' => $qty,
                'equity' => $equity,
                'drift_threshold' => $this->maxDriftThreshold,
                'position_threshold' => $this->maxPositionSizeThreshold,
            ],
        ];

        // Log drift bilgisi
        $this->logDriftInfo($symbol, $driftPct, $riskLevel, $actions);

        return $result;
    }

    /**
     * Drift yüzdesini hesapla
     *
     * @param  float  $entryPrice  Giriş fiyatı
     * @param  float  $currentPrice  Mevcut fiyat
     * @param  string  $side  LONG/SHORT
     * @return float Drift yüzdesi (pozitif = profit, negatif = loss)
     */
    private function calculateDriftPercentage(float $entryPrice, float $currentPrice, string $side): float
    {
        if ($side === 'LONG') {
            return (($currentPrice - $entryPrice) / $entryPrice) * 100;
        } else {
            return (($entryPrice - $currentPrice) / $entryPrice) * 100;
        }
    }

    /**
     * Pozisyon büyüklüğü yüzdesini hesapla
     *
     * @param  float  $qty  Pozisyon miktarı
     * @param  float  $currentPrice  Mevcut fiyat
     * @param  float  $equity  Toplam equity
     * @return float Pozisyon büyüklüğü yüzdesi
     */
    private function calculatePositionSizePercentage(float $qty, float $currentPrice, float $equity): float
    {
        if ($equity <= 0) {
            return INF; // Division by zero
        }

        $positionValue = $qty * $currentPrice;

        return ($positionValue / $equity) * 100;
    }

    /**
     * Risk seviyesini belirle
     *
     * @param  float  $driftPct  Drift yüzdesi
     * @param  float  $positionSizePct  Pozisyon büyüklüğü yüzdesi
     * @return string Risk seviyesi
     */
    private function determineRiskLevel(float $driftPct, float $positionSizePct): string
    {
        // Drift threshold kontrolü (>= kullan)
        $driftRisk = abs($driftPct) >= $this->maxDriftThreshold;

        // Pozisyon büyüklüğü kontrolü (>= kullan)
        $sizeRisk = $positionSizePct >= $this->maxPositionSizeThreshold;

        if ($driftRisk && $sizeRisk) {
            return 'CRITICAL';
        } elseif ($driftRisk || $sizeRisk) {
            return 'HIGH';
        } elseif (abs($driftPct) >= ($this->maxDriftThreshold * 0.7)) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Gerekli aksiyonları belirle
     *
     * @param  string  $riskLevel  Risk seviyesi
     * @param  float  $driftPct  Drift yüzdesi
     * @param  float  $positionSizePct  Pozisyon büyüklüğü yüzdesi
     * @param  array<string, mixed>  $opts  Ek options
     * @return array<string, mixed> Aksiyon listesi
     */
    private function determineActions(
        string $riskLevel,
        float $driftPct,
        float $positionSizePct,
        array $opts
    ): array {
        $actions = [];

        switch ($riskLevel) {
            case 'CRITICAL':
                $actions[] = 'IMMEDIATE_POSITION_REDUCTION';
                $actions[] = 'STOP_LOSS_TIGHTENING';
                $actions[] = 'LEVERAGE_REDUCTION';
                $actions[] = 'EMERGENCY_EXIT_IF_NEEDED';
                break;

            case 'HIGH':
                $actions[] = 'POSITION_SIZE_REDUCTION';
                $actions[] = 'STOP_LOSS_ADJUSTMENT';
                $actions[] = 'MONITOR_CLOSELY';
                break;

            case 'MEDIUM':
                $actions[] = 'SET_ALERTS';
                $actions[] = 'PREPARE_EXIT_STRATEGY';
                break;

            case 'LOW':
                $actions[] = 'CONTINUE_MONITORING';
                break;
        }

        // Drift yönüne göre ek aksiyonlar
        if ($driftPct <= -$this->maxDriftThreshold) {
            $actions[] = 'CONSIDER_AVERAGING_DOWN';
        } elseif ($driftPct >= $this->maxDriftThreshold) {
            $actions[] = 'CONSIDER_TAKING_PROFITS';
        }

        // Pozisyon büyüklüğüne göre ek aksiyonlar
        if ($positionSizePct > $this->maxPositionSizeThreshold) {
            $actions[] = 'REDUCE_POSITION_SIZE';
        }

        return array_unique($actions);
    }

    /**
     * Drift bilgisini logla
     *
     * @param  string  $symbol  Sembol
     * @param  float  $driftPct  Drift yüzdesi
     * @param  string  $riskLevel  Risk seviyesi
     * @param  array  $actions  Aksiyonlar
     */
    private function logDriftInfo(string $symbol, float $driftPct, string $riskLevel, array $actions): void
    {
        $logLevel = match ($riskLevel) {
            'CRITICAL' => 'critical',
            'HIGH' => 'warning',
            'MEDIUM' => 'info',
            'LOW' => 'debug',
            default => 'info'
        };

        Log::log($logLevel, 'Position drift detected', [
            'symbol' => $symbol,
            'drift_percentage' => $driftPct,
            'risk_level' => $riskLevel,
            'actions' => $actions,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Pozisyon drift alarmı kur
     *
     * @param  string  $symbol  Sembol
     * @param  float  $threshold  Drift threshold
     * @param  array  $opts  Ek options
     * @return array Alarm kurulum sonucu
     */
    public function setupDriftAlarm(
        string $symbol,
        float $threshold,
        array $opts = []
    ): array {
        // Drift alarm kurulumu (gerçek implementasyonda exchange API kullanılır)
        $alarmParams = [
            'symbol' => $symbol,
            'threshold' => $threshold,
            'type' => 'DRIFT_ALERT',
            'enabled' => true,
            'interval' => $this->driftCheckInterval,
        ];

        // Şimdilik basit bir mock response
        $response = [
            'ok' => true,
            'alarm_id' => 'drift_'.uniqid(),
            'details' => $alarmParams,
        ];

        return $response;
    }

    /**
     * Drift alarm'ı kapat
     *
     * @param  string  $alarmId  Alarm ID
     * @return array Kapatma sonucu
     */
    public function closeDriftAlarm(string $alarmId): array
    {
        try {
            // Drift alarm kapatma (gerçek implementasyonda exchange API kullanılır)
            $response = [
                'ok' => true,
                'alarm_id' => $alarmId,
                'status' => 'CLOSED',
                'details' => [],
            ];

            return $response;
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'alarm_id' => $alarmId,
                'error' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Toplu drift kontrolü (birden fazla pozisyon için)
     *
     * @param  array  $positions  Pozisyon listesi
     * @param  float  $equity  Toplam equity
     * @return array Toplu drift kontrol sonucu
     */
    public function checkBulkDrift(array $positions, float $equity): array
    {
        $results = [];
        $overallRisk = 'LOW';
        $totalActions = [];

        foreach ($positions as $position) {
            $result = $this->checkPositionDrift(
                $position['symbol'],
                $position['side'],
                $position['entry_price'],
                $position['current_price'],
                $position['qty'],
                $equity
            );

            $results[] = $result;

            // Genel risk seviyesini güncelle
            if ($result['risk_level'] === 'CRITICAL') {
                $overallRisk = 'CRITICAL';
            } elseif ($result['risk_level'] === 'HIGH' && $overallRisk !== 'CRITICAL') {
                $overallRisk = 'HIGH';
            } elseif ($result['risk_level'] === 'MEDIUM' && ! in_array($overallRisk, ['CRITICAL', 'HIGH'])) {
                $overallRisk = 'MEDIUM';
            }

            // Tüm aksiyonları topla
            $totalActions = array_merge($totalActions, $result['actions']);
        }

        return [
            'ok' => $overallRisk !== 'CRITICAL',
            'overall_risk' => $overallRisk,
            'total_positions' => count($positions),
            'risk_distribution' => [
                'CRITICAL' => count(array_filter($results, fn ($r) => $r['risk_level'] === 'CRITICAL')),
                'HIGH' => count(array_filter($results, fn ($r) => $r['risk_level'] === 'HIGH')),
                'MEDIUM' => count(array_filter($results, fn ($r) => $r['risk_level'] === 'MEDIUM')),
                'LOW' => count(array_filter($results, fn ($r) => $r['risk_level'] === 'LOW')),
            ],
            'recommended_actions' => array_unique($totalActions),
            'position_results' => $results,
        ];
    }

    /**
     * Drift guard konfigürasyonunu güncelle
     *
     * @param  array  $config  Yeni konfigürasyon
     * @return array Güncelleme sonucu
     */
    public function updateConfig(array $config): array
    {
        $allowedKeys = ['maxDriftThreshold', 'maxPositionSizeThreshold', 'driftCheckInterval'];

        foreach ($config as $key => $value) {
            if (in_array($key, $allowedKeys) && property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return [
            'ok' => true,
            'updated_config' => [
                'maxDriftThreshold' => $this->maxDriftThreshold,
                'maxPositionSizeThreshold' => $this->maxPositionSizeThreshold,
                'driftCheckInterval' => $this->driftCheckInterval,
            ],
        ];
    }
}
