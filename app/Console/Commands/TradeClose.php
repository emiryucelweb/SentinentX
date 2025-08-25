<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\Exchange\BybitClient;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Notifier\TelegramNotifier;
use App\Services\Trading\PnlService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TradeClose extends Command
{
    protected $signature = 'trade:close {symbol}';

    protected $description = 'Açık pozisyonu hemen kapat (reduce-only market) ve PnL (fee dahil) hesapla';

    public function __construct(
        private BybitClient $bybit,
        private InstrumentInfoService $inst,
        private TelegramNotifier $tg,
        private PnlService $pnl
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper((string) $this->argument('symbol'));

        /** @var Trade|null $trade */
        $trade = Trade::where('symbol', $symbol)->where('status', 'OPEN')->latest('id')->first();
        if (! $trade) {
            $this->warn("Açık trade yok: $symbol");

            return self::SUCCESS;
        }

        $sideOpp = $trade->side === 'LONG' ? 'Sell' : 'Buy';
        $orderLinkId = (string) Str::uuid();

        $resp = $this->bybit->closeReduceOnlyMarket($symbol, $sideOpp, (string) $trade->qty, $orderLinkId);
        if ((int) data_get($resp, 'retCode') !== 0) {
            $this->error('Bybit close error: '.json_encode($resp));

            return self::FAILURE;
        }

        $trade->update(['status' => 'CLOSED', 'closed_at' => now()]);
        $p = $this->pnl->computeAndPersist($trade, $orderLinkId);

        $this->tg->send(
            "🔴 CLOSE (manual) $symbol qty={$trade->qty}\n".
            '💰 PnL (net, fee dahil): '.number_format($p['net'], 4).' USDT ('
            .number_format($p['pnl_pct'], 2)."%)\n".
            '🧾 Fees: '.number_format($p['fees_total'], 4).'   AvgClose: '
            .number_format((float) $p['avg_close'], 2)
        );

        $this->info("Kapatıldı: $symbol  netPnL=".round($p['net'], 4).' ('.round($p['pnl_pct'], 2).'%)');

        return self::SUCCESS;
    }
}
