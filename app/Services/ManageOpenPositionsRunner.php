<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Notifier\AlertDispatcher;
use App\Models\Trade;
use App\Services\Trading\PositionManager;
use App\Services\Trading\TradeCloser;
use Illuminate\Support\Str;

final class ManageOpenPositionsRunner
{
    /** @unused - Placeholder for future functionality */
    private ?object $lock = null;

    /** @unused - Placeholder for future functionality */
    private ?object $info = null;

    /** @unused - Placeholder for future functionality */
    private ?object $market = null;

    /** @unused - Placeholder for future functionality */
    private ?object $consensus = null;

    public function __construct(
        /** @unused - Placeholder for future functionality */
        private readonly PositionManager $manager,
        private readonly TradeCloser $closer,
        private readonly AlertDispatcher $alerts,
    ) {}

    public function run(): void
    {
        Trade::query()->where('status', 'OPEN')->orderBy('id')->chunk(50, function ($trades) {
            foreach ($trades as $t) {
                $this->manageSingle($t);
            }
        });
    }

    private function manageSingle(Trade $t): void
    {
        $key = "cycle:manage:{$t->id}";
        $this->lock->acquire($key, 90, function () use ($t) {
            $cycleId = (string) Str::uuid();
            $symbol = $t->symbol;
            $cat = $this->info->categoryFor($symbol);

            // 1) Piyasa ve trade bağlamı
            $snap = $this->market->snapshot($symbol);
            $payload = [
                'cycle_id' => $cycleId,
                'mode' => 'MANAGE',          // AI’ya ipucu
                'symbol' => $symbol,
                'price' => $snap['price'] ?? null,
                'kline' => $snap['kline'] ?? null,
                'indicators' => $snap['indicators'] ?? null,
                'trade' => [
                    'id' => $t->id,
                    'side' => $t->side,      // LONG | SHORT
                    'entry_price' => (float) $t->entry_price,
                    'tp_price' => ($t->tp_price ?? null) ? (float) $t->tp_price : null,
                    'sl_price' => ($t->sl_price ?? null) ? (float) $t->sl_price : null,
                ],
            ];

            // 2) Konsensüs (yine 2 tur)
            $res = $this->consensus->decide($payload);
            $meta = $res['consensus_meta'] ?? [];
            $tpNew = isset($meta['tp']) && $meta['tp'] > 0 ? (float) $meta['tp'] : null;
            $slNew = isset($meta['sl']) && $meta['sl'] > 0 ? (float) $meta['sl'] : null;

            // 3) Karar haritalama:
            //    - Eğer modeller açıkça action döndürdüyse onu kullan
            //    - Yoksa: final_decision mevcut yönle aynı → HOLD/AMEND; tam tersi ve güven yüksek → CLOSE
            $final = strtoupper((string) ($res['final_decision'] ?? 'NEUTRAL'));
            $confidence = (int) ($meta['confidence'] ?? 0);
            $action = $this->decideAction($final, $confidence, $t->side, $res['stage2'] ?? []);

            if ($action === 'CLOSE') {
                $this->closer->closeNow($t); // anında kapat
                $this->alerts->send(
                    'INFO',
                    'Trade closed by AI',
                    "trade_id={$t->id} symbol={$symbol}",
                    "manage:close:{$t->id}"
                );

                return;
            }

            if ($action === 'AMEND' && ($tpNew || $slNew)) {
                try {
                    $this->bybit->setTradingStop($symbol, $tpNew, $slNew, $cat);
                    $t->update([
                        'tp_price' => $tpNew ?: $t->tp_price,
                        'sl_price' => $slNew ?: $t->sl_price,
                    ]);
                    $this->alerts->send(
                        'INFO',
                        'Stops amended by AI',
                        "trade_id={$t->id} symbol={$symbol} tp={$tpNew} sl={$slNew}",
                        "manage:amend:{$t->id}"
                    );
                } catch (\Throwable $e) {
                    $this->alerts->send(
                        'WARN',
                        'Amend failed',
                        "trade_id={$t->id} {$e->getMessage()}",
                        "manage:amend:fail:{$t->id}"
                    );
                }

                return;
            }

            // HOLD (ya da AMEND ama değer değişmemiş): no-op
        });
    }

    /** FINAL karar + meta ve 2. tur raw alanlarından aksiyon çıkarır */
    private function decideAction(string $final, int $conf, string $currentSide, array $r2): string
    {
        // Öncelik: modeller 'position_action' döndürdüyse
        $actions = [];
        foreach (['gpt', 'gemini', 'grok'] as $m) {
            $a = strtoupper((string) ($r2[$m]->raw['position_action'] ?? ''));
            if (in_array($a, ['CLOSE', 'EXIT', 'AMEND', 'HOLD'], true)) {
                $actions[] = $a === 'EXIT' ? 'CLOSE' : $a;
            }
        }
        if ($this->hasMajority($actions, 'CLOSE')) {
            return 'CLOSE';
        }
        if ($this->hasMajority($actions, 'AMEND')) {
            return 'AMEND';
        }
        if ($this->hasMajority($actions, 'HOLD')) {
            return 'HOLD';
        }

        // Aksiyon yoksa: yön mantığı
        $final = in_array($final, ['LONG', 'SHORT'], true) ? $final : 'NEUTRAL';

        // Final mevcut yöne karşıysa ve güven ≥ 70 → CLOSE
        if ($final !== 'NEUTRAL' && $final !== strtoupper($currentSide) && $conf >= 70) {
            return 'CLOSE';
        }

        // Aynı yönse: AMEND (eğer yeni tp/sl önerilmişse) yoksa HOLD → üstte AMEND check’i karar verir
        if ($final === strtoupper($currentSide)) {
            return 'AMEND';
        }

        return 'HOLD';
    }

    private function hasMajority(array $arr, string $target): bool
    {
        if (count($arr) < 2) {
            return false;
        }
        $count = 0;
        foreach ($arr as $a) {
            if ($a === $target) {
                $count++;
            }
        }

        return $count >= 2;
    }
}
