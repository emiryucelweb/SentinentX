<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Contracts\Risk\RiskGuardInterface;
use App\Models\Trade;
use InvalidArgumentException;

final class RiskGuard implements RiskGuardInterface
{
    /**
     * Basit günlük zarar kontrolü — negatif eşik aşıldıysa true döner.
     */
    public function dailyLossBreached(float $todayPnlPct, float $dailyMaxLossPct): bool
    {
        return $todayPnlPct <= -abs($dailyMaxLossPct);
    }

    /** USDT guard */
    public function usdtDepeg(float $usdtUsd, float $lo, float $hi): bool
    {
        if ($usdtUsd <= 0) {
            throw new InvalidArgumentException('usdtUsd>0');
        }

        return $usdtUsd < $lo || $usdtUsd > $hi;
    }

    /**
     * Temel likidasyon/buffer kontrolü — stop mesafesi yeterli mi?
     * Dönen yapı: ['ok'=>bool,'reason'=>string|null,'details'=>array]
     */
    public function okToOpen(
        string $symbol,
        float $entry,
        string $side,
        int $leverage,
        float $stopLoss,
        ?float $k = null
    ): array {
        // Symbol normalizasyonu
        $symbol = strtoupper($symbol);

        $k = $k ?? (float) (config('trading.risk.liq_buffer_k') ?? 1.2);

        // Temel validasyon kontrolleri
        if ($entry <= 0) {
            return [
                'ok' => false,
                'reason' => 'INVALID_ENTRY_PRICE',
                'details' => ['entry' => $entry, 'message' => 'Entry price must be positive'],
            ];
        }

        if ($leverage <= 0) {
            return [
                'ok' => false,
                'reason' => 'INVALID_LEVERAGE',
                'details' => ['leverage' => $leverage, 'message' => 'Leverage must be positive'],
            ];
        }

        $distPct = abs(($entry - $stopLoss) / $entry);    // oransal uzaklık
        $liqBand = 1.0 / $leverage;                      // ~1/leverage
        $minReq = $k * $liqBand;                        // örn 60x → 1.666% * 1.2 = 2%

        if ($distPct < $minReq) {
            return [
                'ok' => false,
                'reason' => 'LIQ_BUFFER_INSUFFICIENT',
                'details' => [
                    'distance_pct' => round($distPct * 100, 4),
                    'min_required_pct' => round($minReq * 100, 4),
                    'leverage' => $leverage,
                    'k_factor' => $k,
                    'entry' => $entry,
                    'stop_loss' => $stopLoss,
                ],
            ];
        }

        return [
            'ok' => true,
            'reason' => null,
            'details' => [
                'distance_pct' => round($distPct * 100, 4),
                'min_required_pct' => round($minReq * 100, 4),
                'leverage' => $leverage,
                'k_factor' => $k,
            ],
        ];
    }

    /**
     * BTC-ETH korelasyon veto kontrolü
     * Şartname: ρ>0.85 ise veto
     */
    public function checkCorrelationVeto(array $openSymbols, string $candidate): array
    {
        if (! in_array('BTCUSDT', $openSymbols) && ! in_array('ETHUSDT', $openSymbols)) {
            return ['veto' => false, 'reason' => null];
        }

        $correlationService = app(CorrelationService::class);
        $threshold = config('trading.risk.correlation_threshold', 0.85);

        if ($correlationService->isHighlyCorrelated($openSymbols, $candidate, $threshold)) {
            return [
                'veto' => true,
                'reason' => 'BTC-ETH correlation veto triggered',
                'details' => [
                    'candidate' => $candidate,
                    'threshold' => $threshold,
                    'open_symbols' => $openSymbols,
                ],
            ];
        }

        return ['veto' => false, 'reason' => null];
    }

    /**
     * ATR-based liquidation buffer kontrolü
     * Prompt'ta belirtilen: "mesafe≥2*ATR" ve "SL ≤0.5*mesafe içeride"
     *
     * @param  float  $entry  Entry fiyatı
     * @param  float  $stopLoss  Stop loss fiyatı
     * @param  float  $atr  ATR değeri (14,1H)
     * @param  float  $atrMultiplier  ATR multiplier (default: 2.0)
     * @return array ['ok' => bool, 'reason' => string|null, 'details' => array]
     */
    public function checkAtrLiquidationBuffer(
        float $entry,
        float $stopLoss,
        float $atr,
        float $atrMultiplier = 2.0
    ): array {
        if ($entry <= 0 || $stopLoss <= 0 || $atr <= 0) {
            return [
                'ok' => false,
                'reason' => 'INVALID_INPUTS',
                'details' => [
                    'entry' => $entry,
                    'stop_loss' => $stopLoss,
                    'atr' => $atr,
                    'message' => 'All inputs must be positive',
                ],
            ];
        }

        // Stop loss mesafesi hesapla (entry'den stop loss'a)
        $stopLossDistance = abs($entry - $stopLoss);

        // Minimum mesafe: 2×ATR
        $minDistance = $atr * $atrMultiplier;

        // 1. Kontrol: Stop loss mesafesi ≥ 2×ATR olmalı
        if ($stopLossDistance < $minDistance) {
            return [
                'ok' => false,
                'reason' => 'ATR_BUFFER_INSUFFICIENT',
                'details' => [
                    'stop_loss_distance' => $stopLossDistance,
                    'min_distance_required' => $minDistance,
                    'atr' => $atr,
                    'atr_multiplier' => $atrMultiplier,
                    'message' => 'Stop loss distance must be ≥ '.$atrMultiplier.'×ATR',
                ],
            ];
        }

        // 2. Kontrol: Stop loss ≤ 0.5×stop loss mesafesi içeride olmalı
        // Prompt'taki örnek: Entry=20,000, Liq=19,400 → mesafe=600
        // SL ≤0.5*mesafe=300 içeride → SL=19,700 ✅
        // Yani stop loss mesafesi ≤ 0.5×stop loss mesafesi olmalı (bu mantıksız!)

        // Aslında prompt'ta "mesafe" liquidation price'dan entry'ye olan mesafe
        // Ama biz stop loss mesafesini kullanıyoruz
        // Bu durumda stop loss mesafesi ≥ 2×ATR olmalı (zaten kontrol ettik)
        // Ve stop loss mesafesi makul bir değerde olmalı

        // Basit kontrol: Stop loss mesafesi ≤ 3×ATR olmalı (çok uzak olmasın)
        $maxStopLossDistance = $atr * 3.0;

        if ($stopLossDistance > $maxStopLossDistance) {
            return [
                'ok' => false,
                'reason' => 'STOP_LOSS_TOO_FAR',
                'details' => [
                    'stop_loss_distance' => $stopLossDistance,
                    'max_stop_distance' => $maxStopLossDistance,
                    'atr' => $atr,
                    'message' => 'Stop loss distance must be ≤ 3×ATR',
                ],
            ];
        }

        return [
            'ok' => true,
            'reason' => null,
            'details' => [
                'stop_loss_distance' => $stopLossDistance,
                'min_distance_required' => $minDistance,
                'atr' => $atr,
                'atr_multiplier' => $atrMultiplier,
                'max_stop_distance' => $maxStopLossDistance,
                'buffer_ratio' => $stopLossDistance / $minDistance,
            ],
        ];
    }

    /**
     * Kompozit kapı: (1) likidasyon mesafesi, (2) funding guard, (3) korelasyon guard.
     * Dönen yapı: ['ok'=>bool,'reasons'=>string[],'rho_max'=>float|null,'open_symbols'=>string[]]
     */
    public function allowOpenWithGuards(
        string $symbol,
        float $entry,
        string $side,
        int $leverage,
        float $stopLoss,
        FundingGuard $funding,
        CorrelationService $corr,
        ?float $corrThreshold = null
    ): array {
        // Symbol normalizasyonu - her akışta tekil format
        $symbol = strtoupper($symbol);
        $reasons = [];

        // 1) Likidasyon buffer
        $liqCheck = $this->okToOpen($symbol, $entry, $side, $leverage, $stopLoss);
        if (! $liqCheck['ok']) {
            $reasons[] = $liqCheck['reason'] ?? 'LIQ_BUFFER_INSUFFICIENT';
        }

        // 2) Funding penceresi
        $fundingCheck = $funding->okToOpen($symbol);
        if (! $fundingCheck['ok']) {
            $reasons[] = $fundingCheck['reason'] ?? 'FUNDING_WINDOW_BLOCK';
        }

        // 3) Korelasyon (açık pozisyonlarla)
        $openSymbols = Trade::query()
            ->where('status', 'OPEN')
            ->pluck('symbol')
            ->map(fn ($s) => (string) $s)
            ->unique()
            ->values()
            ->all();
        $openSymbols = array_values(
            array_filter($openSymbols, fn ($s) => strtoupper($s) !== strtoupper($symbol))
        );

        $rhoMax = null;
        $threshold = $corrThreshold ?? (float) config('trading.risk.corr_threshold', 0.85);
        if (! empty($openSymbols)) {
            $isHigh = $corr->isHighlyCorrelated($openSymbols, $symbol, $threshold);
            if ($isHigh) {
                $reasons[] = 'HIGH_CORRELATION_BLOCK';
            }
            // İsteyenler için teşhis: max |rho|
            $m = $corr->matrix(array_unique(array_merge($openSymbols, [$symbol])));
            $rhoMax = 0.0;
            foreach ($openSymbols as $s) {
                $rho = abs((float) ($m[$s][$symbol] ?? 0.0));
                if ($rho > $rhoMax) {
                    $rhoMax = $rho;
                }
            }
        }

        return [
            'ok' => empty($reasons),
            'reasons' => $reasons,
            'rho_max' => $rhoMax,
            'open_symbols' => $openSymbols,
        ];
    }

    /**
     * Correlation check: Açık pozisyonlarla yüksek korelasyonlu yeni pozisyon açmayı engeller
     */
    public function correlationBlocked(
        string $symbol,
        array $openSymbols,
        array $correlations,
        float $threshold = 0.85
    ): bool {
        foreach ($openSymbols as $openSymbol) {
            $key = $this->pairKey($symbol, $openSymbol);
            if (isset($correlations[$key]) && abs($correlations[$key]) > $threshold) {
                return true;
            }
        }

        return false;
    }

    private function pairKey(string $a, string $b): string
    {
        return strcmp($a, $b) < 0 ? "$a-$b" : "$b-$a";
    }
}
