<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

final class PromptFactory
{
    /**
     * @param  array  $ctx  Market/trade bağlamı (snapshot, indicators, balance vs.)
     * @return array{prompt:string,schema:array}
     */
    /**
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    public function newPositionR1(array $ctx): array
    {
        $symbols = implode(', ', $this->allowedSymbols($ctx));
        $prompt = <<<TXT
SENİN ROLÜN
- Bybit vadeli işlemlerde tek bir sembol için (izinli: {$symbols}) yeni POZİSYON açmaya karar veren 
  risk-uyumlu bir al-sat analisti olarak davran.
- Sadece kapanmış mum ve verilen metriklerle çalış. Yarım mum veya "tahmin" yok.

GİRDİLER
- Sembol: {symbol}
- Son fiyat: {price}
- Kapanmış mum özeti ve teknikler: {indicators}
- Coingecko/sentiment özeti (varsa): {coingecko}
- Açık pozisyon bilgisi: {open_positions}  (AÇIK POZİSYON VARSA YENİ AÇMA!)
- USDT bakiyesi (normalize): {balance}
- Risk parametreleri: max_daily_loss, liq_safety_k, default_leverage = {risk}

GÖREV
- Sadece şu 4 sembolden biri için karar ver: BTCUSDT, ETHUSDT, SOLUSDT, XRPUSDT.
- Kararın: "LONG", "SHORT" veya "NO_TRADE".
- Eğer "LONG/SHORT" dersen: kaldıraç 30–75 aralığında; TP ve SL **fiyat** öner.
- Nedenini kısa teknik gerekçeyle yaz (indicator adları ve eşikleriyle).
- **Yarım mumlara dayanma. Açık pozisyon varsa NO_TRADE.**

ÇIKIŞ
- Sadece aşağıdaki JSON'u döndür. Metin ekleme.
{
  "decision": "LONG | SHORT | NO_TRADE",
  "confidence": 0-100,
  "leverage": 30-75,
  "take_profit": <number>,   // fiyat
  "stop_loss": <number>,     // fiyat
  "reason": "kısa teknik gerekçe"
}
TXT;

        $filled = $this->fill($prompt, $ctx);

        return ['prompt' => $filled, 'schema' => $this->schemaNew()];
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $r1
     * @return array<string, mixed>
     */
    public function newPositionR2(array $ctx, array $r1): array
    {
        $symbols = implode(', ', $this->allowedSymbols($ctx));
        $r1json = json_encode($this->r1Compact($r1), JSON_UNESCAPED_SLASHES);
        $prompt = <<<TXT
SENİN ROLÜN
- Üç farklı modelin TUR-1 sonuçlarını, aynı veri setiyle değerlendirip **konsolide TUR-2** önerisi üret.

KISITLAR
- Yalnızca bu semboller: {$symbols}
- Açık pozisyon varsa "NO_TRADE".
- Kaldıraç: 30–75
- TP/SL mantıklı ve likidasyondan güvenli uzaklıkta.

TUR-1 SONUÇLARI (özet):
{$r1json}

GÖREV
- Eğer TUR-1 çoğunluğu aynı yöndeyse o yöne daha iyi ayarlanmış lev/TP/SL ver.
- Çoğunluk yoksa yine **net karar** ver; gerekçeni kısaca yaz.
- Karar "NO_TRADE" olabilir.

ÇIKIŞ (yalnızca JSON):
{
  "decision": "LONG | SHORT | NO_TRADE",
  "confidence": 0-100,
  "leverage": 30-75,
  "take_profit": <number>,
  "stop_loss": <number>,
  "reason": "kısa konsolidasyon gerekçesi"
}
TXT;

        $filled = $this->fill($prompt, $ctx);

        return ['prompt' => $filled, 'schema' => $this->schemaNew()];
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    public function manageR1(array $ctx): array
    {
        $prompt = <<<'TXT'
SENİN ROLÜN
- HALİHAZIRDA AÇIK OLAN bir pozisyonu yönet: "HOLD", "AMEND" (TP/SL güncelle), ya da "CLOSE" (kapat).
- Sadece kapanmış mum ve verilen metriklerle çalış.

GİRDİLER
- Trade: {trade}   // side, entry_price, tp/sl mevcutsa
- Sembol: {symbol}, Fiyat: {price}
- Teknikler: {indicators}
- Risk parametreleri: {risk}

KARAR SEÇENEKLERİ
- "HOLD": TP/SL aynen kalsın.
- "AMEND": yeni TP/SL fiyatları ver (en az birini değiştir).
- "CLOSE": pozisyonu kapat.

ÇIKIŞ (yalnızca JSON):
{
  "decision": "LONG | SHORT | NO_TRADE",
  "confidence": 0-100,
  "take_profit": <number|null>,
  "stop_loss": <number|null>,
  "reason": "kısa gerekçe",
  "position_action": "HOLD | AMEND | CLOSE"
}
NOTLAR
- "position_action" alanı ZORUNLU.
- "decision" alanı yön tahminidir; yönetim aksiyonu "position_action" ile alınır.
- "AMEND" dersen, TP ve/veya SL ver. "CLOSE" dersen TP/SL boş olabilir.
TXT;

        $filled = $this->fill($prompt, $ctx);

        return ['prompt' => $filled, 'schema' => $this->schemaManage()];
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $r1
     * @return array<string, mixed>
     */
    public function manageR2(array $ctx, array $r1): array
    {
        $r1json = json_encode($this->r1Compact($r1, true), JSON_UNESCAPED_SLASHES);
        $prompt = <<<TXT
SENİN ROLÜN
- Açık pozisyon yönetiminde TUR-1 çıktılarının konsolidasyonu: "HOLD", "AMEND", "CLOSE" arasında nihai öneri.

TUR-1 SONUÇLARI:
{$r1json}

GÖREV
- Çoğunluk "CLOSE" ise kapatma yönünde netleş.
- Çoğunluk "AMEND" ise makul tek bir TP/SL seti öner.
- Eşitlikte, en yüksek güvene ve son fiyat/ATR bağlamına göre karar ver.

ÇIKIŞ (yalnız JSON):
{
  "decision": "LONG | SHORT | NO_TRADE",
  "confidence": 0-100,
  "take_profit": <number|null>,
  "stop_loss": <number|null>,
  "reason": "kısa konsolidasyon gerekçesi",
  "position_action": "HOLD | AMEND | CLOSE"
}
TXT;

        $filled = $this->fill($prompt, $ctx);

        return ['prompt' => $filled, 'schema' => $this->schemaManage()];
    }

    private function allowedSymbols(array $ctx): array
    {
        return ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];
    }

    /** R1 kararlarını sadeleştir (decision, conf, lev/tp/sl varsa) */
    private function r1Compact(array $r1, bool $includeAction = false): array
    {
        $out = [];
        foreach (['gpt', 'gemini', 'grok'] as $m) {
            $row = [
                'decision' => $r1[$m]->decision ?? null,
                'confidence' => $r1[$m]->confidence ?? null,
            ];
            $raw = $r1[$m]->raw ?? [];
            foreach (['leverage' => 'leverage'] as $k => $src) {
                if (isset($raw[$src])) {
                    $row[$k] = $raw[$src];
                }
            }
            if ($includeAction) {
                $row['position_action'] = $raw['position_action'] ?? null;
            }
            $row['tp'] = $r1[$m]->takeProfit ?? null;
            $row['sl'] = $r1[$m]->stopLoss ?? null;
            $out[$m] = $row;
        }

        return $out;
    }

    private function schemaNew(): array
    {
        return [
            'decision' => ['enum' => ['LONG', 'SHORT', 'NO_TRADE']],
            'confidence' => ['type' => 'integer', 'min' => 0, 'max' => 100],
            'leverage' => ['type' => 'integer', 'min' => 30, 'max' => 75],
            'take_profit' => ['type' => 'number'],
            'stop_loss' => ['type' => 'number'],
            'reason' => ['type' => 'string'],
        ];
    }

    private function schemaManage(): array
    {
        return [
            'decision' => ['enum' => ['LONG', 'SHORT', 'NO_TRADE']],
            'confidence' => ['type' => 'integer', 'min' => 0, 'max' => 100],
            'take_profit' => ['type' => ['number', 'null']],
            'stop_loss' => ['type' => ['number', 'null']],
            'reason' => ['type' => 'string'],
            'position_action' => ['enum' => ['HOLD', 'AMEND', 'CLOSE']],
        ];
    }

    /** Basit placeholder doldurma */
    private function fill(string $prompt, array $ctx): string
    {
        $r = [
            '{symbol}' => (string) ($ctx['symbol'] ?? 'UNKNOWN'),
            '{price}' => (string) ($ctx['price'] ?? 'NA'),
            '{indicators}' => json_encode($ctx['indicators'] ?? $ctx['kline'] ?? [], JSON_UNESCAPED_SLASHES),
            '{coingecko}' => json_encode($ctx['coingecko'] ?? [], JSON_UNESCAPED_SLASHES),
            '{open_positions}' => json_encode($ctx['open_positions'] ?? [], JSON_UNESCAPED_SLASHES),
            '{balance}' => json_encode($ctx['balance'] ?? [], JSON_UNESCAPED_SLASHES),
            '{risk}' => json_encode($ctx['risk'] ?? [], JSON_UNESCAPED_SLASHES),
            '{trade}' => json_encode($ctx['trade'] ?? [], JSON_UNESCAPED_SLASHES),
        ];

        return strtr($prompt, $r);
    }
}
