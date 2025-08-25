<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;

final class AiScoringService
{
    /** @return array<string,float> */
    public function currentWeights(): array
    {
        // ai_providers tablosundan oku; yoksa 1.0
        $rows = DB::table('ai_providers')->where('enabled', 1)->get(['name', 'weight']);
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->name] = max(0.1, (float) $r->weight);
        }

        return array_merge($out, ['gpt' => 1.0, 'gemini' => 1.0, 'grok' => 1.0]);
    }

    /** Basit günlük skor güncelleme: win_rate ve gecikmeye göre ağırlık */
    public function updateWeightsFromHistory(): void
    {
        // Kapanmış tradeler üzerinden AI performansı (örn. son 200 trade)
        // Burada minimal bir örnek bırakıyoruz; projede ai_logs ↔ trades etiketlemesi yapılmış.
        // İyileştirme: Brier skoru, latency cezası, üstel çürüme vb.
        $providers = ['gpt', 'gemini', 'grok'];
        foreach ($providers as $p) {
            $win = (int) DB::table('ai_logs')
                ->where('model', $p)
                ->where('used_in_consensus', 1)
                ->whereNotNull('trade_id')
                ->join('trades', 'trades.id', '=', 'ai_logs.trade_id')
                ->where('trades.status', 'CLOSED')
                ->where('trades.pnl', '>', 0)
                ->count();
            $tot = (int) DB::table('ai_logs')
                ->where('model', $p)
                ->whereNotNull('trade_id')
                ->join('trades', 'trades.id', '=', 'ai_logs.trade_id')
                ->where('trades.status', 'CLOSED')
                ->count();
            $wr = $tot > 0 ? $win / $tot : 0.5;         // win rate
            $w = max(0.5, min(1.5, 0.5 + $wr));    // 0.5..1.5 aralığı
            DB::table('ai_providers')->updateOrInsert(['name' => $p], ['weight' => $w, 'enabled' => 1]);
        }
    }
}
