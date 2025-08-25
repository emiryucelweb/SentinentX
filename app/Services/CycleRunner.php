<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Notifier\AlertDispatcher;
use App\Contracts\Risk\CorrelationServiceInterface;
use App\Contracts\Support\LockManager;
use App\Models\Trade;
use App\Services\AI\ConsensusService;
use App\Services\Exchange\AccountService;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Market\BybitMarketData;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuardInterface;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use App\Services\Trading\TradeManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CycleRunner
{
    private ConsensusService $consensus;

    private BybitMarketData $market;

    private AccountService $account;

    private InstrumentInfoService $info;

    private mixed $risk;

    private PositionSizer $sizer;

    private StopCalculator $stopCalc;

    private TradeManager $trader;

    private FundingGuard $funding;

    private CorrelationServiceInterface $correlation;

    private AlertDispatcher $alerts;

    private mixed $lock;

    public function __construct(mixed ...$args)
    {
        foreach ($args as $arg) {
            if ($arg instanceof ConsensusService) {
                $this->consensus = $arg;

                continue;
            }
            if ($arg instanceof BybitMarketData) {
                $this->market = $arg;

                continue;
            }
            if ($arg instanceof AccountService) {
                $this->account = $arg;

                continue;
            }
            if ($arg instanceof InstrumentInfoService) {
                $this->info = $arg;

                continue;
            }
            if ($arg instanceof RiskGuardInterface || (is_object($arg) && method_exists($arg, 'allowOpenWithGuards'))) {
                $this->risk = $arg;

                continue;
            }
            if ($arg instanceof PositionSizer) {
                $this->sizer = $arg;

                continue;
            }
            if ($arg instanceof StopCalculator) {
                $this->stopCalc = $arg;

                continue;
            }
            if ($arg instanceof TradeManager) {
                $this->trader = $arg;

                continue;
            }
            if ($arg instanceof FundingGuard) {
                $this->funding = $arg;

                continue;
            }
            if ($arg instanceof CorrelationServiceInterface) {
                $this->correlation = $arg;

                continue;
            }
            if ($arg instanceof AlertDispatcher) {
                $this->alerts = $arg;

                continue;
            }
            if ($arg instanceof LockManager || (is_object($arg) && method_exists($arg, 'acquire')) || str_contains(get_class($arg), 'LockManager')) {
                $this->lock = $arg;

                continue;
            }
        }

        // Debug: Hangi parametrelerin geldiğini göster
        $missing = [];
        foreach (
            [
                'consensus', 'market', 'account', 'info', 'risk', 'sizer',
                'stopCalc', 'trader', 'funding', 'correlation', 'alerts', 'lock',
            ] as $p
        ) {
            if (! isset($this->$p)) {
                $missing[] = $p;
            }
        }
        if ($missing) {
            // Debug bilgisi ekle
            $argTypes = array_map(fn ($arg) => get_class($arg), $args);
            $assignedProps = [];
            foreach (['consensus', 'market', 'account', 'info', 'risk', 'sizer', 'stopCalc', 'trader', 'funding', 'correlation', 'alerts', 'lock'] as $prop) {
                $assignedProps[] = $prop.'='.(isset($this->$prop) ? 'YES' : 'NO');
            }
            throw new InvalidArgumentException(
                'CycleRunner missing deps: '.implode(', ', $missing)
                .'. Received args: '.implode(', ', $argTypes)
                .'. Props: '.implode(', ', $assignedProps)
            );
        }
    }

    public function run(string $symbol): void
    {
        $this->runForSymbol($symbol);
    }

    /**
     * runSymbol() alias - Queue/job tarafında okunaklılık için
     */
    public function runSymbol(string $symbol): void
    {
        $this->run($symbol);
    }

    private function runForSymbol(string $symbol): void
    {
        Log::debug('CYCLE_RUNNER_START', ['symbol' => $symbol]);
        $this->lock->acquire("cycle:new:$symbol", 120, function () use ($symbol) {
            $cycleId = (string) Str::uuid();
            Log::debug('CYCLE_RUNNER_LOCK_ACQUIRED', ['symbol' => $symbol, 'cycle_id' => $cycleId]);

            // 1) Gerekli tüm verileri topla
            $snap = $this->market->snapshot($symbol) ?: ['symbol' => $symbol];
            $instrumentInfo = $this->info->get($symbol);
            $price = (float) ($snap['price'] ?? 0.0);
            if ($price <= 0) {
                return;
            }

            $atr = (float) ($snap['atr'] ?? $this->deriveAtrFromKline($snap));
            if ($atr <= 0) {
                $atr = $price * 0.003;
            }

            // 2) AI Konsensüs kararını al
            $dec = $this->consensus->decide(
                ['symbol' => $symbol, 'price' => $price, 'atr' => $atr] + $snap
            );
            $final = $dec['final'] ?? [];
            $action = strtoupper((string) ($final['action'] ?? 'HOLD'));
            $conf = (int) ($final['confidence'] ?? 0);

            if (! in_array($action, ['LONG', 'SHORT'], true) || $conf < (int) config('trading.ai.min_confidence', 60)) {
                return;
            }

            // 3) Boyutlandırma, SL/TP hesaplamaları
            $equity = (float) ($this->account->equity() ?? 0.0);
            $riskPct = (float) config('trading.risk.per_trade_risk_pct', 1.0);
            $lev = max(3, min(75, (int) config('trading.mode.max_leverage', 75)));

            $sl = $final['stopLoss'] ?? $this->stopCalc->atrStop($action, $price, $atr);
            $tp = $final['takeProfit'] ?? $this->stopCalc->atrTakeProfit($action, $price, $atr);

            $qtyStep = (float) ($instrumentInfo['lotSizeFilter']['qtyStep'] ?? 0.001);
            $minQty = (float) ($instrumentInfo['lotSizeFilter']['minOrderQty'] ?? 0.001);
            $sizeResult = $this->sizer->sizeByRisk($equity, $riskPct, $atr, $price, $lev, $qtyStep, $minQty);
            $qty = (float) ($sizeResult['qty'] ?? 0.0);

            $qf = (float) ($final['qtyDeltaFactor'] ?? 1.0);
            $qty = round($qty * max(0.25, min(2.0, $qf)), 8);

            if ($qty <= 0) {
                return;
            }

            // 4) Risk kapılarını kontrol et (config ile açılabilir)
            $enableGate = (bool) config('trading.risk.enable_composite_gate', false);
            Log::debug('RISK_GATE_CONFIG', ['enable_composite_gate' => $enableGate, 'risk_object' => get_class($this->risk)]);
            if ($enableGate) {
                $gate = $this->risk->allowOpenWithGuards(
                    $symbol,
                    $price,
                    $action,
                    $lev,
                    $sl,
                    $this->funding,
                    $this->correlation
                );
                if (! $gate['ok']) {
                    // Risk gate log'u ekle
                    Log::channel('risk')->warning('RISK_GATE_BLOCK', [
                        'symbol' => $symbol,
                        'action' => $action,
                        'reasons' => $gate['reasons'] ?? [],
                        'rho_max' => $gate['rho_max'] ?? null,
                        'open_symbols' => $gate['open_symbols'] ?? [],
                        'cycle_uuid' => $cycleId,
                    ]);

                    // AlertDispatcher ile RISK_GATE_BLOCK uyarısı gönder
                    $this->alerts->send(
                        'warn',
                        'RISK_GATE_BLOCK',
                        'Risk gate blocked opening',
                        [
                            'symbol' => $symbol,
                            'action' => $action,
                            'reasons' => $gate['reasons'] ?? [],
                            'gate_result' => $gate,
                        ],
                        "risk_gate:{$symbol}:{$action}:".date('Y-m-d-H').':'
                        .app()->environment()
                    );

                    // Her risk nedeni için ayrı alert gönder
                    foreach ($gate['reasons'] as $reason) {
                        $dedupKey = "risk_gate:{$symbol}:{$reason}:".date('Y-m-d-H').':'.app()->environment();
                        $alertMessage = "Risk gate blocked {$symbol} {$action}: {$reason}";

                        // Risk nedeni detaylarını ekle
                        $alertData = [
                            'symbol' => $symbol,
                            'action' => $action,
                            'reason' => $reason,
                            'gate_result' => $gate,
                        ];

                        $this->alerts->send('warn', $reason, $alertMessage, $alertData, $dedupKey);
                    }

                    return;
                }
            }

            // 5) One-Way modu kontrolü (hedge kapalı iken karşı yön engeli)
            $accountMode = strtoupper((string) (config('trading.mode.account') ?? 'ONE_WAY'));
            if ($accountMode === 'ONE_WAY') {
                $open = Trade::query()
                    ->where('symbol', $symbol)
                    ->where('status', 'OPEN')
                    ->orderByDesc('id')
                    ->first();

                if ($open && strtoupper((string) $open->side) !== $action) {
                    // Karşı yön açmaya çalışma: engelle ve uyar
                    $this->alerts->send(
                        'warn',
                        'ONE_WAY_BLOCK',
                        "One-Way mode: opposite side blocked for {$symbol}",
                        [
                            'symbol' => $symbol,
                            'current_open_side' => $open->side,
                            'attempted_side' => $action,
                            'cycle_uuid' => $cycleId,
                        ],
                        "one_way_block:{$symbol}:".date('Y-m-d-H').':'.app()->environment()
                    );

                    return; // Emir gönderme
                }
            }

            // 6) Emir gönder
            try {
                $hasMethod = method_exists($this->trader, 'openWithFallback');
                Log::debug('TRADE_MANAGER_CHECK', ['has_method' => $hasMethod, 'trader_class' => get_class($this->trader)]);
                if ($hasMethod) {
                    $result = $this->trader->openWithFallback($symbol, $action, $price, $qty, $atr);

                    // Trade kaydı oluştur
                    \App\Models\Trade::create([
                        'symbol' => $symbol,
                        'side' => $action,
                        'status' => 'OPEN',
                        'margin_mode' => 'ISOLATED',
                        'leverage' => $lev,
                        'qty' => $qty,
                        'entry_price' => $price,
                        'take_profit' => $tp,
                        'stop_loss' => $sl,
                        'bybit_order_id' => $result['orderId'] ?? null,
                        'opened_at' => now(),
                        'meta' => json_encode(['cycle_id' => $cycleId], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            } catch (Throwable $e) {
                Log::channel('trading')->error('TRADE_OPEN_FAILED', [
                    'symbol' => $symbol,
                    'action' => $action,
                    'price' => $price,
                    'qty' => $qty,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                $this->alerts->send(
                    'error',
                    'TRADE_OPEN_FAILED',
                    'Trade open failed',
                    [
                        'symbol' => $symbol,
                        'action' => $action,
                        'price' => $price,
                        'qty' => $qty,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'cycle_uuid' => $cycleId,
                    ],
                    'trade_open_failed:'.$symbol.':'.date('Y-m-d-H').':'.app()->environment()
                );

                return;
            }
        });
    }

    private function deriveAtrFromKline(array $snap): float
    {
        $rows = $snap['kline'] ?? [];
        if (count($rows) < 15) {
            return 0.0;
        }
        $trs = [];
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $h = (float) $rows[$i][2];
            $l = (float) $rows[$i][3];
            $prevClose = (float) $rows[$i + 1][4];
            $trs[] = max($h - $l, abs($h - $prevClose), abs($l - $prevClose));
        }

        return count($trs) > 0 ? array_sum($trs) / count($trs) : 0.0;
    }
}
