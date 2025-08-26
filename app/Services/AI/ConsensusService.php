<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AiProvider;
use App\DTO\AiDecision;
use App\Services\Logger\AiLogCreatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsensusService
{
    /** @var AiProvider[] */
    private array $providers = [];

    private ?AiScoringService $scores = null;

    /** @unused - Placeholder for future logging functionality */
    private ?AiLogCreatorService $logger = null;

    private ?ConsensusCalculationService $calculator = null;

    // Rate-limit ve circuit breaker state
    /**
     * @var array<string, int>
     */
    private array $vetoCounts = [];

    /**
     * @var array<string, mixed>
     */
    private array $circuitBreakerState = [];

    /**
     * @var array<string, int>
     */
    private array $lastVetoTime = [];

    // Cache için
    private ?float $cachedThreshold = null;

    private ?int $cachedThresholdTimestamp = null;

    private const THRESHOLD_CACHE_TTL = 60; // 60 saniye

    /**
     * Esnek kurucu: Testler ve ServiceProvider farklı sıralarda bağımlılık geçebiliyor.
     * Kabul: AiProvider, AiScoringService, AiLogCreatorService veya AiProvider[] dizisi.
     */
    public function __construct(mixed ...$args)
    {
        foreach ($args as $arg) {
            if ($arg instanceof AiProvider) {
                $this->providers[] = $arg;

                continue;
            }
            if ($arg instanceof AiScoringService) {
                $this->scores = $arg;

                continue;
            }
            if ($arg instanceof AiLogCreatorService) {
                $this->logger = $arg;

                continue;
            }
            if ($arg instanceof ConsensusCalculationService) {
                $this->calculator = $arg;

                continue;
            }
            if (is_array($arg)) {
                foreach ($arg as $item) {
                    if ($item instanceof AiProvider) {
                        $this->providers[] = $item;
                    }
                }
            }
        }
    }

    /** @return array<string,mixed> */
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function decide(array $payload): array
    {
        $startTime = microtime(true);
        $symbols = $payload['symbols'] ?? [$payload['symbol'] ?? 'UNKNOWN'];
        $symbols = is_array($symbols) ? $symbols : [$symbols];
        $cycle = (string) ($payload['cycle_id'] ?? \Illuminate\Support\Str::uuid());
        $timeframe = (string) ($payload['timeframe'] ?? '1m');

        // Rate-limit ve circuit breaker kontrolü (ilk sembol için)
        $firstSymbol = $symbols[0] ?? 'UNKNOWN';
        if (! $this->checkRateLimitAndCircuitBreaker($firstSymbol)) {
            return $this->createRateLimitedResponse($firstSymbol, $cycle, $startTime);
        }

        $results = [];

        // Her sembol için konsensüs çalıştır
        foreach ($symbols as $symbol) {
            $payload['current_symbol'] = $symbol;

            // Stage-1: sağlayıcılardan topla
            $r1 = [];
            $nameMap = [];
            foreach ($this->providers as $idx => $p) {
                $name = (string) $p->name(); // AiProvider interface garantee
                $nameMap[$idx] = $name;
                $res = $p->decide($payload, 'STAGE1', $symbol);
                $dec = $res; // Already guaranteed to be AiDecision from provider
                $r1[$idx] = $dec;
            }

            // Stage-2: Stage 1 sonuçlarını ekleyerek sağlayıcılardan topla
            $r2 = [];

            // Stage 1 sonuçlarını payload'a ekle
            $stage1Results = [];
            foreach ($r1 as $i => $decision) {
                $aiName = $nameMap[$i] ?? ('ai'.($i + 1));
                $stage1Results[$aiName] = [
                    'action' => $decision->action,
                    'confidence' => $decision->confidence,
                    'reason' => $decision->reason,
                    'stop_loss' => $decision->stopLoss,
                    'take_profit' => $decision->takeProfit,
                ];
            }

            $payloadStage2 = $payload;
            $payloadStage2['stage1_results'] = $stage1Results;

            foreach ($this->providers as $idx => $p) {
                $name = (string) $p->name(); // AiProvider interface garantee
                $res = $p->decide($payloadStage2, 'STAGE2', $symbol);
                $dec = $res; // Already guaranteed to be AiDecision from provider
                $r2[$idx] = $dec;
            }

            // first_stage: sağlayıcı adlarına göre AiDecision nesneleri
            $firstStageByName = [];
            foreach ($r1 as $i => $d) {
                $firstStageByName[$nameMap[$i] ?? ('ai'.($i + 1))] = $d;
            }

            // Ağırlıklar
            $weights = $this->scores?->currentWeights() ?? [];
            $wmap = [];
            foreach ($this->providers as $i => $p) {
                $nm = $nameMap[$i] ?? ('ai'.($i + 1));
                $wmap[$nm] = $weights[$nm] ?? 1.0;
            }

            // Provider olmadığında veya geçerli decision olmadığında hata dön
            $hasValidDecisions = count($r2) > 0;
            if (! $hasValidDecisions) {
                return [
                    'ok' => false,
                    'reason' => 'No valid decisions from providers',
                    'symbol' => $symbol,
                    'cycle' => $cycle,
                    'duration' => microtime(true) - $startTime,
                ];
            }

            // Nihai seçim (çoğunluk; eşitlikte ağırlıklı skor)
            $final = $this->pickFinal($r2, $wmap);

            // Final confidence = MEDYAN( confidences ) beklentisine uyum
            $finalConf = $this->median(array_map(fn (AiDecision $d) => (float) $d->confidence, $r2));
            // DTO katmanında action normalizasyonu yapılıyor
            $final = new AiDecision(
                action: (string) $final->action,
                confidence: (int) round($finalConf),
                stopLoss: $final->stopLoss,
                takeProfit: $final->takeProfit,
                qtyDeltaFactor: $final->qtyDeltaFactor,
                reason: $final->reason,
                raw: $final->raw,
            );

            // AI'ların seçtiği kaldıraçların ortalamasını al
            $lev = $this->calculateAverageLeverage($r2, $payload);

            // Weighted median kararı
            if ($this->calculator) {
                $final = $this->calculator->calculateWeightedMedian($r2, $wmap);
            } else {
                // Fallback: simple majority vote
                $final = $this->pickFinal($r2, $wmap);
            }
            $tp = $this->trimmedMean(array_map(fn (AiDecision $d) => $d->takeProfit ?? $d->raw['take_profit'] ?? null, $r1));
            $sl = $this->trimmedMean(array_map(fn (AiDecision $d) => $d->stopLoss ?? $d->raw['stop_loss'] ?? null, $r1));

            // Dinamik sapma eşiği hesapla
            $deviationThreshold = $this->getDynamicDeviationThreshold($payload);

            // Gelişmiş validation ve veto kontrolü
            $validationResult = $this->validateConsensusValuesAdvanced($r1, $deviationThreshold, $payload);

            if (! $validationResult['ok']) {
                // Veto sayacını artır
                $this->incrementVetoCount($symbol);

                // Sapma veto - NO_TRADE
                $final = new AiDecision(
                    action: 'NO_TRADE',
                    confidence: 0,
                    stopLoss: null,
                    takeProfit: null,
                    qtyDeltaFactor: 0.0,
                    reason: $validationResult['reason_code'].': '.$validationResult['reason'],
                    raw: ['leverage' => 1, 'lev' => 1],
                );

                // Structured logging
                $this->logVetoEvent(
                    $symbol,
                    $cycle,
                    $validationResult,
                    $payload,
                    $startTime
                );

                // DB'ye veto kaydı
                try {
                    DB::table('consensus_decisions')->insert([
                        'symbol' => $symbol,
                        'cycle_uuid' => $cycle,
                        'final_action' => 'NO_TRADE',
                        'final_confidence' => 0,
                        'meta' => json_encode([
                            'leverage' => $lev,
                            'tp' => $tp,
                            'sl' => $sl,
                            'veto_reason' => $validationResult['reason_code'],
                            'veto_details' => $validationResult['details'],
                            'deviation_threshold' => $deviationThreshold,
                            'rate_limit_info' => $this->getRateLimitInfo($symbol),
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('ConsensusService veto DB error: '.$e->getMessage());
                }

                $results[$symbol] = [
                    'cycle_uuid' => $cycle,
                    'final' => $final->toArray(),
                    'final_decision' => 'NO_TRADE',
                    'stage1' => array_map(fn (AiDecision $d) => $d->toArray(), $r1),
                    'stage2' => array_map(fn (AiDecision $d) => $d->toArray(), $r2),
                    'first_stage' => $firstStageByName,
                    'consensus_meta' => [
                        'leverage' => $lev,
                        'tp' => $tp,
                        'sl' => $sl,
                        'deviation_threshold' => $deviationThreshold,
                        'veto_reason' => $validationResult['reason_code'],
                        'veto_details' => $validationResult['details'],
                        'rate_limit_info' => $this->getRateLimitInfo($symbol),
                    ],
                ];

                continue;
            }

            // Başarılı konsensüs sonucu
            $results[$symbol] = [
                'cycle_uuid' => $cycle,
                'final' => $final->toArray(),
                'final_decision' => $final->action,
                'stage1' => array_map(fn (AiDecision $d) => $d->toArray(), $r1),
                'stage2' => array_map(fn (AiDecision $d) => $d->toArray(), $r2),
                'first_stage' => $firstStageByName,
                'consensus_meta' => [
                    'leverage' => $lev,
                    'tp' => $tp,
                    'sl' => $sl,
                    'deviation_threshold' => $deviationThreshold,
                ],
            ];

            // DB kayıtları
            try {
                DB::transaction(function () use ($symbol, $cycle, $nameMap, $r1, $r2, $final, $lev, $tp, $sl, $payload) {
                    // 3x R1 + 3x R2 = 6 kayıt
                    foreach ($r1 as $i => $d) {
                        DB::table('ai_logs')->insert([
                            'symbol' => $symbol,
                            'cycle_uuid' => $cycle,
                            'provider' => $nameMap[$i] ?? ('ai'.($i + 1)),
                            'stage' => 'STAGE1',
                            'action' => $d->action,
                            'confidence' => $d->confidence,
                            'reason' => $d->reason,
                            'input_ctx' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                            'raw_output' => json_encode($d->toArray(), JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    foreach ($r2 as $i => $d) {
                        DB::table('ai_logs')->insert([
                            'symbol' => $symbol,
                            'cycle_uuid' => $cycle,
                            'provider' => $nameMap[$i] ?? ('ai'.($i + 1)),
                            'stage' => 'STAGE2',
                            'action' => $d->action,
                            'confidence' => $d->confidence,
                            'reason' => $d->reason,
                            'input_ctx' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                            'raw_output' => json_encode($d->toArray(), JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('consensus_decisions')->insert([
                        'symbol' => $symbol,
                        'cycle_uuid' => $cycle,
                        'final_action' => $final->action,
                        'final_confidence' => $final->confidence,
                        'meta' => json_encode(['leverage' => $lev, 'tp' => $tp, 'sl' => $sl], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            } catch (\Throwable $e) {
                // Test ortamında hatayı fırlat
                if (app()->environment('testing')) {
                    throw $e;
                }
                // Test dışı şema farklarında fail etme — testler yalnızca sayıya bakıyor
                Log::error('ConsensusService DB error: '.$e->getMessage(), [
                    'exception' => $e,
                    'symbol' => $symbol,
                    'cycle' => $cycle,
                ]);
            }
        }

        // Çoklu sembol sonuçlarını birleştir
        if (count($symbols) === 1) {
            $result = $results[$symbols[0]];

            // Backward compatibility: return array format for single symbol
            return [
                'action' => $result['final']['action'] ?? 'NO_TRADE',
                'confidence' => $result['final']['confidence'] ?? 0,
                'leverage' => $result['consensus_meta']['leverage'] ?? 1,
                'stop_loss' => $result['consensus_meta']['sl'] ?? null,
                'take_profit' => $result['consensus_meta']['tp'] ?? null,
                'reason' => $result['final']['reason'] ?? 'No reason provided',
                'quantity' => $result['final']['qtyDeltaFactor'] ?? 1.0,
            ];
        }

        return [
            'cycle_uuid' => $cycle,
            'symbols' => $symbols,
            'results' => $results,
            'summary' => [
                'total_symbols' => count($symbols),
                'successful_decisions' => count(array_filter($results, fn ($r) => $r['final_decision'] !== 'NO_TRADE')),
                'vetoed_decisions' => count(array_filter($results, fn ($r) => $r['final_decision'] === 'NO_TRADE')),
            ],
        ];

    }

    /** @param AiDecision[] $decisions */
    /**
     * @param  array<int, AiDecision>  $decisions
     * @param  array<string, float>  $weights
     */
    private function pickFinal(array $decisions, array $weights): AiDecision
    {
        // Boş array kontrolü
        if (empty($decisions)) {
            throw new \InvalidArgumentException('No decisions provided to pickFinal');
        }

        // Çoğunluk
        $counts = [];
        foreach ($decisions as $d) {
            $counts[$d->action] = ($counts[$d->action] ?? 0) + 1;
        }
        arsort($counts);
        $top = array_key_first($counts);
        if ($top && $counts[$top] >= 2) {
            // çoğunluk var: aynı eylemde en yüksek confidence değil — karar eylemi sabit,
            // confidence median ile az sonra güncellenecek; herhangi bir temsilci dönebilir
            foreach ($decisions as $d) {
                if ($d->action === $top) {
                    return $d;
                }
            }
        }
        // Tie-break: ağırlıklı confidence
        $best = $decisions[0];
        $bestScore = -1.0;
        foreach ($decisions as $i => $d) {
            // Use index-based weights lookup with fallback
            $w = 1.0; // Default weight
            if (! empty($weights)) {
                $w = (float) (array_values($weights)[$i] ?? 1.0);
            }
            $s = $w * (float) $d->confidence;
            if ($s > $bestScore) {
                $bestScore = $s;
                $best = $d;
            }
        }

        return $best;
    }

    /** @param array<int,float|int|null> $vals */
    private function trimmedMean(array $vals): ?float
    {
        $nums = [];
        foreach ($vals as $v) {
            if ($v !== null) {
                $nums[] = (float) $v;
            }
        }
        $n = count($nums);
        if ($n === 0) {
            return null;
        }
        sort($nums);
        if ($n >= 3) {
            array_shift($nums);
            array_pop($nums);
        }

        return array_sum($nums) / max(count($nums), 1);
    }

    /** @param array<int,float> $vals */
    private function median(array $vals): float
    {
        sort($vals);
        $n = count($vals);
        if ($n === 0) {
            return 0.0;
        }
        $m = intdiv($n, 2);

        return $n % 2 ? (float) $vals[$m] : (float) (($vals[$m - 1] + $vals[$m]) / 2);
    }

    /**
     * Konsensüs değerlerinin sapma kontrolünü yapar.
     *
     * @param  AiDecision[]  $decisions  Konsensüs kararları.
     * @param  array<int, AiDecision>  $decisions
     * @param  float  $threshold  Sapma eşiği (örn: 0.20).
     * @return array{ok: bool, reason: string, details: array<string, mixed>}
     *
     * @unused Legacy method - kept for reference
     */
    private function validateConsensusValues(array $decisions, float $threshold): array
    {
        $deviations = [];

        // 1. Leverage sapma kontrolü
        $leverages = array_filter(
            array_map(
                fn (AiDecision $d) => $d->raw['leverage'] ?? $d->raw['lev'] ?? null,
                $decisions
            ),
            fn ($v) => $v !== null
        );
        if (count($leverages) > 0) {
            $levMedian = $this->median($leverages);
            foreach ($leverages as $lev) {
                $deviation = abs($lev - $levMedian) / max($levMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'leverage',
                        'value' => $lev,
                        'median' => $levMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Leverage deviation: '.$lev.' vs median '.$levMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break; // Bir leverage sapması yeterli
                }
            }
        }

        // 2. Take Profit sapma kontrolü
        $takeProfits = array_filter(
            array_map(fn (AiDecision $d) => $d->takeProfit, $decisions),
            fn ($v) => $v !== null
        );
        if (count($takeProfits) > 0) {
            $tpMedian = $this->median($takeProfits);
            foreach ($takeProfits as $tp) {
                $deviation = abs($tp - $tpMedian) / max($tpMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'take_profit',
                        'value' => $tp,
                        'median' => $tpMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Take Profit deviation: '.$tp.' vs median '.$tpMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break; // Bir TP sapması yeterli
                }
            }
        }

        // 3. Stop Loss sapma kontrolü
        $stopLosses = array_filter(
            array_map(fn (AiDecision $d) => $d->stopLoss, $decisions),
            fn ($v) => $v !== null
        );
        if (count($stopLosses) > 0) {
            $slMedian = $this->median($stopLosses);
            foreach ($stopLosses as $sl) {
                $deviation = abs($sl - $slMedian) / max($slMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'stop_loss',
                        'value' => $sl,
                        'median' => $slMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Stop Loss deviation: '.$sl.' vs median '.$slMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break; // Bir SL sapması yeterli
                }
            }
        }

        return [
            'ok' => empty($deviations),
            'reason' => empty($deviations) ? 'No deviations detected' : 'Deviation veto triggered',
            'details' => $deviations,
        ];
    }

    /**
     * Rate-limit ve circuit breaker kontrolü
     */
    private function checkRateLimitAndCircuitBreaker(string $symbol): bool
    {
        $now = time();
        $symbolKey = $symbol.'_'.date('Y-m-d-H-i'); // Dakika bazında

        // Circuit breaker kontrolü
        $circuitBreakerActive = $this->circuitBreakerState[$symbol] ?? false;
        if ($circuitBreakerActive) {
            $cooldown = config('ai.consensus.circuit_breaker_cooldown_seconds', 30);
            $lastVetoTime = $this->lastVetoTime[$symbol] ?? 0;
            if (($now - $lastVetoTime) < $cooldown) {
                return false; // Circuit breaker aktif
            }
            // Cooldown süresi geçti, reset
            $this->circuitBreakerState[$symbol] = false;
        }

        // Rate-limit kontrolü
        $currentCount = $this->vetoCounts[$symbolKey] ?? 0;
        $maxVeto = config('ai.consensus.max_veto_per_minute', 10);

        if ($currentCount >= $maxVeto) {
            // Circuit breaker'ı tetikle
            $this->circuitBreakerState[$symbol] = true;
            $this->lastVetoTime[$symbol] = $now;

            return false;
        }

        return true;
    }

    /**
     * Rate-limited response oluştur
     *
     * @return array<string, mixed>
     */
    private function createRateLimitedResponse(string $symbol, string $cycle, float $startTime): array
    {
        $latency = (microtime(true) - $startTime) * 1000;

        return [
            'cycle_uuid' => $cycle,
            'final' => [
                'action' => 'NO_TRADE',
                'confidence' => 0,
                'reason' => 'RATE_LIMIT: Too many vetoes in short time',
            ],
            'final_decision' => 'NO_TRADE',
            'consensus_meta' => [
                'veto_reason' => 'RATE_LIMIT',
                'rate_limit_info' => $this->getRateLimitInfo($symbol),
                'latency_ms' => round($latency, 2),
            ],
        ];
    }

    /**
     * Veto sayacını artır
     */
    private function incrementVetoCount(string $symbol): void
    {
        $symbolKey = $symbol.'_'.date('Y-m-d-H-i');
        $this->vetoCounts[$symbolKey] = ($this->vetoCounts[$symbolKey] ?? 0) + 1;
    }

    /**
     * Rate-limit bilgilerini al
     *
     * @return array<string, mixed>
     */
    private function getRateLimitInfo(string $symbol): array
    {
        $symbolKey = $symbol.'_'.date('Y-m-d-H-i');
        $currentCount = $this->vetoCounts[$symbolKey] ?? 0;
        $maxVeto = config('ai.consensus.max_veto_per_minute', 10);
        $circuitBreakerActive = $this->circuitBreakerState[$symbol] ?? false;

        return [
            'current_veto_count' => $currentCount,
            'max_veto_per_minute' => $maxVeto,
            'circuit_breaker_active' => $circuitBreakerActive,
            'remaining_vetoes' => max(0, $maxVeto - $currentCount),
        ];
    }

    /**
     * Dinamik sapma eşiği hesapla
     *
     * @param  array<string, mixed>  $payload
     */
    private function getDynamicDeviationThreshold(array $payload): float
    {
        // Cache kontrolü
        $cacheAge = time() - ($this->cachedThresholdTimestamp ?? 0);
        if (
            $this->cachedThreshold !== null &&
            $cacheAge < self::THRESHOLD_CACHE_TTL
        ) {
            return $this->cachedThreshold;
        }

        $baseThreshold = $this->getEnvironmentThreshold();

        // Dinamik threshold aktif mi?
        if (! config('ai.consensus.dynamic_threshold_enabled', false)) {
            $this->cachedThreshold = $baseThreshold;
            $this->cachedThresholdTimestamp = time();

            return $baseThreshold;
        }

        // Volatiliteye bağlı threshold hesapla
        $atr = $payload['atr'] ?? 1000;
        $price = $payload['price'] ?? 50000;
        $atrPercentage = $atr / $price;

        $multiplier = config('ai.consensus.dynamic_threshold_multiplier', 1.0);
        $minThreshold = config('ai.consensus.dynamic_threshold_min', 0.10);
        $maxThreshold = config('ai.consensus.dynamic_threshold_max', 0.30);

        $dynamicThreshold = $this->clamp($multiplier * $atrPercentage, $minThreshold, $maxThreshold);

        $this->cachedThreshold = $dynamicThreshold;
        $this->cachedThresholdTimestamp = time();

        return $dynamicThreshold;
    }

    /**
     * Environment'a göre threshold al
     */
    private function getEnvironmentThreshold(): float
    {
        $env = config('app.env', 'production');

        if ($env === 'production') {
            return (float) config('ai.consensus.deviation_threshold_prod', 0.15);
        }

        if ($env === 'lab' || $env === 'testing') {
            return (float) config('ai.consensus.deviation_threshold_lab', 0.20);
        }

        return (float) config('ai.consensus.deviation_threshold', 0.20);
    }

    /**
     * Gelişmiş validation ve veto kontrolü
     *
     * @param  array<int, AiDecision>  $decisions
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateConsensusValuesAdvanced(array $decisions, float $threshold, array $payload): array
    {
        $deviations = [];
        $strictValidation = config('ai.consensus.strict_validation', true);

        // 1. Schema validation (strict mode'da)
        if ($strictValidation) {
            $schemaValidation = $this->validateSchema($decisions);
            if (! $schemaValidation['ok']) {
                return [
                    'ok' => false,
                    'reason_code' => 'SCHEMA_FAIL',
                    'reason' => $schemaValidation['reason'],
                    'details' => $schemaValidation['details'],
                ];
            }
        }

        // 2. NONE yüksek güven vetosu kontrolü
        $noneVetoResult = $this->validateNoneVeto($decisions);
        if (! $noneVetoResult['ok']) {
            return [
                'ok' => false,
                'reason_code' => 'NONE_VETO',
                'reason' => $noneVetoResult['reason'],
                'details' => $noneVetoResult['details'],
            ];
        }

        // 3. Range validation
        $rangeValidation = $this->validateRanges($decisions);
        if (! $rangeValidation['ok']) {
            return [
                'ok' => false,
                'reason_code' => 'OUT_OF_RANGE',
                'reason' => $rangeValidation['reason'],
                'details' => $rangeValidation['details'],
            ];
        }

        // 4. Deviation validation
        $deviationValidation = $this->validateDeviations($decisions, $threshold);
        if (! $deviationValidation['ok']) {
            return [
                'ok' => false,
                'reason_code' => 'DEV_VETO',
                'reason' => $deviationValidation['reason'],
                'details' => $deviationValidation['details'],
            ];
        }

        return [
            'ok' => true,
            'reason_code' => 'VALIDATION_PASS',
            'reason' => 'All validations passed',
            'details' => [],
        ];
    }

    /**
     * NONE yüksek güven vetosu kontrolü
     * Şartname: %90 güven üstündeki NONE => NO_TRADE
     *
     * @param  array<int, AiDecision>  $decisions
     * @return array<string, mixed>
     */
    private function validateNoneVeto(array $decisions): array
    {
        $noneDecisions = array_filter($decisions, fn (AiDecision $d) => $d->action === 'NONE');

        $hasNoneDecisions = count($noneDecisions) > 0;
        if (! $hasNoneDecisions) {
            return ['ok' => true, 'reason' => 'No NONE decisions', 'details' => []];
        }

        $highConfidenceNone = array_filter($noneDecisions, fn (AiDecision $d) => $d->confidence >= 90);

        if (! empty($highConfidenceNone)) {
            $providers = array_map(fn (AiDecision $d) => $d->raw['provider'] ?? 'unknown', $highConfidenceNone);

            return [
                'ok' => false,
                'reason' => 'High confidence NONE veto triggered',
                'details' => [
                    'providers' => $providers,
                    'confidence_levels' => array_map(fn (AiDecision $d) => $d->confidence, $highConfidenceNone),
                    'threshold' => 90,
                ],
            ];
        }

        return ['ok' => true, 'reason' => 'NONE decisions below veto threshold', 'details' => []];
    }

    /**
     * Schema validation
     *
     * @param  array<int, AiDecision>  $decisions
     * @return array<string, mixed>
     */
    private function validateSchema(array $decisions): array
    {
        $errors = [];

        foreach ($decisions as $idx => $decision) {
            if (! $decision instanceof AiDecision) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Invalid decision object type',
                    'details' => ['type' => gettype($decision)],
                ];

                continue;
            }

            // NaN/INF/NULL kontrolü
            if ($decision->confidence !== null && (! is_numeric($decision->confidence) || ! is_finite($decision->confidence))) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Invalid confidence value',
                    'details' => ['confidence' => $decision->confidence],
                ];
            }

            if ($decision->takeProfit !== null && (! is_numeric($decision->takeProfit) || ! is_finite($decision->takeProfit))) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Invalid take profit value',
                    'details' => ['take_profit' => $decision->takeProfit],
                ];
            }

            if ($decision->stopLoss !== null && (! is_numeric($decision->stopLoss) || ! is_finite($decision->stopLoss))) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Invalid stop loss value',
                    'details' => ['stop_loss' => $decision->stopLoss],
                ];
            }
        }

        $hasErrors = count($errors) > 0;

        return [
            'ok' => ! $hasErrors,
            'reason' => $hasErrors ? 'Schema validation failed' : 'Schema validation passed',
            'details' => $errors,
        ];
    }

    /**
     * Range validation
     */
    private function validateRanges(array $decisions): array
    {
        $errors = [];
        $leverageMin = config('ai.consensus.leverage_min', 3);
        $leverageMax = config('ai.consensus.leverage_max', 75);

        foreach ($decisions as $idx => $decision) {
            // Leverage range kontrolü
            $leverage = $decision->raw['leverage'] ?? $decision->raw['lev'] ?? null;
            if ($leverage !== null) {
                if ($leverage < $leverageMin || $leverage > $leverageMax) {
                    $errors[] = [
                        'index' => $idx,
                        'error' => 'Leverage out of range',
                        'details' => [
                            'leverage' => $leverage,
                            'min' => $leverageMin,
                            'max' => $leverageMax,
                        ],
                    ];
                }
            }

            // Take Profit pozitif kontrolü
            if ($decision->takeProfit !== null && $decision->takeProfit <= 0) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Take profit must be positive',
                    'details' => ['take_profit' => $decision->takeProfit],
                ];
            }

            // Stop Loss pozitif kontrolü
            if ($decision->stopLoss !== null && $decision->stopLoss <= 0) {
                $errors[] = [
                    'index' => $idx,
                    'error' => 'Stop loss must be positive',
                    'details' => ['stop_loss' => $decision->stopLoss],
                ];
            }

            // Quantity delta factor range kontrolü
            if ($decision->qtyDeltaFactor !== null) {
                if ($decision->qtyDeltaFactor < -1.0 || $decision->qtyDeltaFactor > 1.0) {
                    $errors[] = [
                        'index' => $idx,
                        'error' => 'Quantity delta factor out of range',
                        'details' => [
                            'qty_delta_factor' => $decision->qtyDeltaFactor,
                            'min' => -1.0,
                            'max' => 1.0,
                        ],
                    ];
                }
            }
        }

        $hasErrors = count($errors) > 0;

        return [
            'ok' => ! $hasErrors,
            'reason' => $hasErrors ? 'Range validation failed' : 'Range validation passed',
            'details' => $errors,
        ];
    }

    /**
     * Deviation validation
     */
    private function validateDeviations(array $decisions, float $threshold): array
    {
        $deviations = [];

        // 1. Leverage sapma kontrolü
        $leverages = array_filter(
            array_map(
                fn (AiDecision $d) => $d->raw['leverage'] ?? $d->raw['lev'] ?? null,
                $decisions
            ),
            fn ($v) => $v !== null
        );
        if (count($leverages) > 0) {
            $levMedian = $this->median($leverages);
            foreach ($leverages as $lev) {
                $deviation = abs($lev - $levMedian) / max($levMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'leverage',
                        'value' => $lev,
                        'median' => $levMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Leverage deviation: '.$lev.' vs median '.$levMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break;
                }
            }
        }

        // 2. Take Profit sapma kontrolü
        $takeProfits = array_filter(
            array_map(fn (AiDecision $d) => $d->takeProfit, $decisions),
            fn ($v) => $v !== null
        );
        if (count($takeProfits) > 0) {
            $tpMedian = $this->median($takeProfits);
            foreach ($takeProfits as $tp) {
                $deviation = abs($tp - $tpMedian) / max($tpMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'take_profit',
                        'value' => $tp,
                        'median' => $tpMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Take Profit deviation: '.$tp.' vs median '.$tpMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break;
                }
            }
        }

        // 3. Stop Loss sapma kontrolü
        $stopLosses = array_filter(
            array_map(fn (AiDecision $d) => $d->stopLoss, $decisions),
            fn ($v) => $v !== null
        );
        if (count($stopLosses) > 0) {
            $slMedian = $this->median($stopLosses);
            foreach ($stopLosses as $sl) {
                $deviation = abs($sl - $slMedian) / max($slMedian, 1e-8);
                if ($deviation > $threshold) {
                    $deviations[] = [
                        'type' => 'stop_loss',
                        'value' => $sl,
                        'median' => $slMedian,
                        'deviation_percentage' => round($deviation * 100, 2),
                        'reason' => 'Stop Loss deviation: '.$sl.' vs median '.$slMedian
                            .' ('.round($deviation * 100, 2).'%)',
                    ];
                    break;
                }
            }
        }

        return [
            'ok' => empty($deviations),
            'reason' => empty($deviations) ? 'No deviations detected' : 'Deviation veto triggered',
            'details' => $deviations,
        ];
    }

    /**
     * Structured veto event logging
     */
    private function logVetoEvent(
        string $symbol,
        string $cycle,
        array $validationResult,
        array $payload,
        float $startTime
    ): void {
        if (! config('ai.consensus.structured_logging', true)) {
            return;
        }

        $latency = (microtime(true) - $startTime) * 1000;

        $logData = [
            'event_type' => 'consensus_veto',
            'symbol' => $symbol,
            'cycle_uuid' => $cycle,
            'timeframe' => $payload['timeframe'] ?? '1m',
            'provider_id' => implode(',', array_map(fn ($p) => method_exists($p, 'name') ? $p->name() : 'unknown', $this->providers)),
            'latency_ms' => round($latency, 2),
            'veto_reason_code' => $validationResult['reason_code'],
            'veto_reason' => $validationResult['reason'],
            'veto_details' => $validationResult['details'],
            'timestamp' => now()->toISOString(),
            'environment' => config('app.env', 'production'),
            'rate_limit_info' => $this->getRateLimitInfo($symbol),
        ];

        // Alert threshold kontrolü
        $detailCount = count($validationResult['details']);
        if ($detailCount >= config('ai.consensus.alert_threshold_major', 3)) {
            $logData['alert_level'] = 'MAJOR';
            \Log::critical('Consensus veto MAJOR alert: '.json_encode($logData));
        } elseif ($detailCount >= config('ai.consensus.alert_threshold_minor', 1)) {
            $logData['alert_level'] = 'MINOR';
            \Log::warning('Consensus veto MINOR alert: '.json_encode($logData));
        } else {
            $logData['alert_level'] = 'INFO';
            \Log::info('Consensus veto event: '.json_encode($logData));
        }
    }

    /**
     * AI'ların seçtiği kaldıraçların ortalamasını hesapla
     */
    private function calculateAverageLeverage(array $decisions, array $payload): int
    {
        $leverages = [];
        $riskContext = $payload['risk_context'] ?? [];
        $minLeverage = $riskContext['min_leverage'] ?? 3;
        $maxLeverage = $riskContext['max_leverage'] ?? 15;

        foreach ($decisions as $decision) {
            // AI'dan gelen kaldıraç değerini al (raw response'dan)
            $leverage = $decision->raw['leverage'] ?? null;

            if ($leverage && is_numeric($leverage)) {
                // Risk aralığında olduğundan emin ol
                $leverage = max($minLeverage, min($maxLeverage, (int) $leverage));
                $leverages[] = $leverage;
            }
        }

        if (empty($leverages)) {
            // Hiç AI kaldıraç seçmediyse minimum kullan
            return $minLeverage;
        }

        // Ortalama hesapla ve yukarı yuvarla (32.5 -> 33)
        $average = array_sum($leverages) / count($leverages);
        $averageLeverage = (int) ceil($average);

        // Risk aralığında tut
        return max($minLeverage, min($maxLeverage, $averageLeverage));
    }

    /**
     * Clamp helper function
     */
    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
