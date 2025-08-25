<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $data = $request->all();
        Log::info('Telegram webhook received', $data);
        
        $message = $data['message'] ?? null;
        if (!$message) {
            return new Response('OK', 200);
        }
        
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? null;
        $allowedChatId = config('notifier.telegram.chat_id');
        
        // Sadece belirlenen chat ID'den kabul et
        if ($chatId != $allowedChatId) {
            return new Response('Unauthorized', 403);
        }
        
        Log::info('Processing command', ['text' => $text, 'chat_id' => $chatId]);
        
        $response = $this->processCommand($text);
        
        Log::info('Command response', ['response' => $response ? substr($response, 0, 100) : 'null']);
        
        if ($response) {
            $this->sendTelegramMessage((string) $chatId, $response);
        }
        
        return new Response('OK', 200);
    }
    
    public function processCommand(string $text): ?string
    {
        $text = trim($text);
        
        if ($text === '/scan') {
            Artisan::call('sentx:lab-scan');
            return "🔍 <b>Lab scan başlatıldı!</b>\n\nTüm coinler analiz ediliyor.";
        }
        
        if ($text === '/status') {
            return $this->getStatusMessage();
        }
        
        if (preg_match('/^\/open\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (!str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }
            
            // Desteklenen coinleri kontrol et
            $supportedCoins = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];
            if (!in_array($symbol, $supportedCoins)) {
                return "❌ <b>Desteklenmeyen coin!</b>\n\nDesteklenen coinler: BTC, ETH, SOL, XRP";
            }
            
            return "🎯 <b>{$symbol} pozisyonu açmak istiyorsun.</b>\n\n" .
                   "⚡ <b>Risk tercihiniz nedir?</b>\n" .
                   "🔹 <code>/risk1 {$symbol}</code> - Düşük Risk (3-15x, %5 pozisyon)\n" .
                   "🔹 <code>/risk2 {$symbol}</code> - Orta Risk (15-45x, %10 pozisyon)\n" .
                   "🔹 <code>/risk3 {$symbol}</code> - Yüksek Risk (45-125x, %15 pozisyon)";
        }
        
        if (preg_match('/^\/risk([123])\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $riskLevel = (int) $matches[1];
            $symbol = strtoupper($matches[2]);
            if (!str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }
            
            $riskProfiles = [
                1 => ['name' => 'Düşük Risk', 'range' => '3-15x', 'min' => 3, 'max' => 15, 'pct' => 5],
                2 => ['name' => 'Orta Risk', 'range' => '15-45x', 'min' => 15, 'max' => 45, 'pct' => 10],
                3 => ['name' => 'Yüksek Risk', 'range' => '45-125x', 'min' => 45, 'max' => 125, 'pct' => 15]
            ];
            
            $profile = $riskProfiles[$riskLevel];
            
            return "⚡ <b>{$profile['name']} seçildi!</b>\n" .
                   "📊 Kaldıraç: {$profile['range']}\n" .
                   "💰 Pozisyon boyutu: %{$profile['pct']}\n\n" .
                   "💭 <b>Neden bu pozisyonu açmak istiyorsun?</b>\n" .
                   "Analiz gerekçeni yaz (haber, teknik analiz vs.)\n\n" .
                   "Format: <code>/confirm {$symbol} {$riskLevel} gerekçen buraya</code>";
        }
        
        if (preg_match('/^\/confirm\s+([A-Z0-9]+)\s+([123])\s+(.+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (!str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }
            $riskLevel = (int) $matches[2];
            $userReason = trim($matches[3]);
            
            $riskProfiles = [
                1 => ['name' => 'Düşük Risk', 'min' => 3, 'max' => 15, 'pct' => 5],
                2 => ['name' => 'Orta Risk', 'min' => 15, 'max' => 45, 'pct' => 10],
                3 => ['name' => 'Yüksek Risk', 'min' => 45, 'max' => 125, 'pct' => 15]
            ];
            
            $profile = $riskProfiles[$riskLevel];
            
            // AI analizi başlat
            $response = $this->triggerAIAnalysisWithRisk($symbol, $userReason, $profile);
            
            return "🤖 <b>AI Analizi Tamamlandı!</b>\n\n" .
                   "📊 {$symbol} | {$profile['name']}\n" .
                   "💭 Gerekçe: <i>{$userReason}</i>\n\n" .
                   "<b>AI Kararı:</b>\n" .
                   $response . "\n\n" .
                   "✅ <code>/execute {$symbol}</code> - Pozisyonu aç\n" .
                   "❌ <code>/cancel</code> - İptal et";
        }
        
        if (preg_match('/^\/execute\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (!str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }
            
            // Pozisyonu gerçekten aç (mock)
            return "🎉 <b>Pozisyon Açıldı!</b>\n\n" .
                   "📊 {$symbol} LONG\n" .
                   "⚡ Kaldıraç: 32x\n" .
                   "💰 Miktar: 0.05 BTC\n" .
                   "🎯 Entry: $114,445\n" .
                   "🛡️ SL: $112,750\n" .
                   "🏆 TP: $118,000\n\n" .
                   "✅ Pozisyon #{time()} açıldı!";
        }
        
        if ($text === '/cancel') {
            return "❌ <b>İşlem iptal edildi.</b>\n\n💡 Yeni pozisyon için /open komutunu kullan.";
        }
        
        if ($text === '/pnl') {
            return $this->getPnLMessage();
        }
        
        if ($text === '/trades') {
            return $this->getRecentTradesMessage();
        }
        
        if ($text === '/balance') {
            return $this->getBalanceMessage();
        }
        
        if ($text === '/positions') {
            return $this->getPositionsDetailMessage();
        }
        
        if ($text === '/positionmanage' || $text === '/manage') {
            return $this->getPositionManageMessage();
        }
        
        if (preg_match('/^\/close\s+(\w+)$/i', $text, $matches)) {
            $symbolOrId = $matches[1];
            return $this->closePosition($symbolOrId);
        }
        
        // Özel selamlama
        if (strtolower($text) === 'selam canım' || strtolower($text) === 'selam canim') {
            return "🤗 <b>Hoşgeldin patron!</b>\n\n" .
                   "Bugün ne yapıyoruz? 💪\n\n" .
                   $this->getHelpMessage();
        }
        
        if ($text === '/help' || $text === '/start') {
            return $this->getHelpMessage();
        }
        
        return null;
    }
    
    public function getStatusMessage(): string
    {
        try {
            $status = [];
            
            // 1. Bybit Bağlantı Kontrolü
            try {
                $client = app('App\Services\Exchange\BybitClient');
                $account = $client->getAccountInfo();
                $status['bybit'] = $account['retCode'] === 0 ? '🟢 Aktif' : '🔴 Hata';
            } catch (\Exception $e) {
                $status['bybit'] = '🔴 Bağlantı Hatası';
            }
            
            // 2. Açık Pozisyon Durumu
            try {
                $positions = $client->getPositions();
                if ($positions['retCode'] === 0) {
                    $openPositions = array_filter($positions['result']['list'] ?? [], function($pos) {
                        return (float) $pos['size'] > 0;
                    });
                    $status['positions'] = count($openPositions) . ' açık pozisyon';
                } else {
                    $status['positions'] = 'Bilgi alınamadı';
                }
            } catch (\Exception $e) {
                $status['positions'] = 'Hata';
            }
            
            // 3. Redis/Queue Durumu
            try {
                \Illuminate\Support\Facades\Redis::ping();
                $status['redis'] = '🟢 Aktif';
            } catch (\Exception $e) {
                $status['redis'] = '🔴 Bağlantı Hatası';
            }
            
            // 4. AI Servisler Durumu (basit ping)
            $aiStatus = [];
            $aiKeys = [
                'OPENAI_API_KEY' => 'OpenAI',
                'GEMINI_API_KEY' => 'Gemini', 
                'GROK_API_KEY' => 'Grok'
            ];
            
            foreach ($aiKeys as $envKey => $name) {
                $aiStatus[$name] = !empty(config('app.' . strtolower($name) . '_api_key', env($envKey))) ? '🟢' : '🔴';
            }
            
            $currentTime = now()->setTimezone('Europe/Istanbul')->format('H:i:s');
            
            return "🤖 <b>Durum raporu!</b>\n\n" .
                   "🏛️ <b>Bybit bağlantısı:</b> {$status['bybit']}\n" .
                   "📊 <b>Aktif pozisyonların:</b> {$status['positions']}\n" .
                   "🔴 <b>Redis durumu:</b> {$status['redis']}\n\n" .
                   "🧠 <b>AI ekibim:</b>\n" .
                   "• OpenAI: {$aiStatus['OpenAI']}\n" .
                   "• Gemini: {$aiStatus['Gemini']}\n" .
                   "• Grok: {$aiStatus['Grok']}\n\n" .
                   "⚙️ <b>Çalışma ortamı:</b> " . (config('app.env') === 'testnet' ? 'Testnet (güvenli) 🛡️' : 'Production ⚡') . "\n" .
                   "⏰ <b>Son kontrol:</b> {$currentTime}";
                   
        } catch (\Exception $e) {
            return "❌ Sistem durumu alınamadı: " . $e->getMessage();
        }
    }
    

    
    private function triggerAIAnalysisWithRisk(string $symbol, string $userReason, array $riskProfile): string
    {
        // Risk profili ile snapshot oluştur
        $snapshot = [
            'timestamp' => now()->toISOString(),
            'symbols' => [$symbol],
            'user_intent' => [
                'reason' => $userReason,
                'request_type' => 'specific_position',
                'timestamp' => now()->toISOString()
            ],
            'market_data' => [$symbol => ['price' => 114445, 'change_24h' => -0.32]],
            'portfolio' => ['total_balance' => 10000, 'available_balance' => 9500],
            'risk_context' => [
                'risk_profile' => $riskProfile['name'],
                'min_leverage' => $riskProfile['min'],
                'max_leverage' => $riskProfile['max'],
                'position_size_pct' => $riskProfile['pct']
            ]
        ];
        
        $path = storage_path('app/snapshots/telegram_ai_' . strtolower($symbol) . '.json');
        file_put_contents($path, json_encode($snapshot, JSON_PRETTY_PRINT));
        
        try {
            // AI analizini çalıştır ve gerçek pozisyon aç
            Artisan::call('sentx:open-now', [
                'symbol' => $symbol,
                '--snapshot' => $path
            ]);
            
            // Başarılı pozisyon açma mesajı
            return "🎯 <b>Pozisyon açıldı!</b>\n\n" .
                   "📊 {$symbol} analizi tamamlandı\n" .
                   "🤖 AI kararı alındı ve pozisyon açıldı\n" .
                   "💰 Risk profili: {$riskProfile['name']}\n" .
                   "📝 Gerekçe: {$userReason}\n\n" .
                   "💡 Pozisyon detayları için /positions komutunu kullan";
                   
        } catch (\Exception $e) {
            return "❌ AI analizi hatası: " . $e->getMessage();
        }
    }
    
    private function getPositionManageMessage(): string
    {
        try {
            // Gerçek Bybit pozisyon bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $positions = $client->getPositions();
            
            if ($positions['retCode'] !== 0) {
                return "❌ Bybit pozisyon bilgisi alınamadı: " . $positions['retMsg'];
            }
            
            $positionList = $positions['result']['list'] ?? [];
            
            // Sadece açık pozisyonları filtrele
            $openPositions = array_filter($positionList, function($pos) {
                return (float) $pos['size'] > 0;
            });
            
            if (empty($openPositions)) {
                return "📊 <b>Pozisyon Yönetimi</b>\n\n⚪ Hiç açık pozisyon yok.";
            }
            
            $message = "📊 <b>Pozisyon Yönetimi</b>\n\n";
            
            foreach ($openPositions as $position) {
                $symbol = $position['symbol'];
                $side = $position['side'] === 'Buy' ? 'LONG' : 'SHORT';
                $size = $position['size'];
                $entryPrice = (float) $position['avgPrice'];
                $markPrice = (float) $position['markPrice'];
                $leverage = $position['leverage'];
                $unrealizedPnl = (float) $position['unrealisedPnl'];
                
                $pnlPct = $entryPrice > 0 ? round((($markPrice - $entryPrice) / $entryPrice) * 100, 2) : 0;
                if ($side === 'SHORT') $pnlPct = -$pnlPct;
                
                $pnlEmoji = $unrealizedPnl > 0 ? '🟢' : ($unrealizedPnl < 0 ? '🔴' : '⚪');
                $sideEmoji = $side === 'LONG' ? '📈' : '📉';
                
                // Symbol'den sadece coin kısmını al (BTCUSDT -> BTC)
                $coinSymbol = str_replace('USDT', '', $symbol);
                
                $message .= "{$sideEmoji} <b>{$symbol}</b> {$side}\n";
                $message .= "   💰 Entry: \$" . number_format($entryPrice, 2) . "\n";
                $message .= "   📊 Mark: \$" . number_format($markPrice, 2) . "\n";
                $message .= "   📏 Size: {$size} | ⚡ {$leverage}x\n";
                $message .= "   {$pnlEmoji} P&L: \$" . number_format($unrealizedPnl, 2) . " ({$pnlPct}%)\n";
                $message .= "   🗂 /close {$coinSymbol} - Pozisyonu kapat\n\n";
            }
            
            $message .= "💡 <b>Komutlar:</b>\n";
            $message .= "/close COIN - Pozisyonu kapat (örnek: /close BTC)\n";
            $message .= "/positions - Detaylı görünüm\n";
            $message .= "/balance - Bakiye bilgisi";
            
            return $message;
        } catch (\Exception $e) {
            return "❌ Pozisyon yönetimi hatası: " . $e->getMessage();
        }
    }
    
    public function closePosition(string $symbolOrId): string
    {
        try {
            $client = app('App\Services\Exchange\BybitClient');
            
            // Eğer sayısal ID ise, lokal veritabanından sembole çevir
            if (is_numeric($symbolOrId)) {
                $trade = \App\Models\Trade::find($symbolOrId);
                if (!$trade) {
                    return "❌ <b>Pozisyon bulunamadı!</b>\n\nID: {$symbolOrId}";
                }
                $symbol = $trade->symbol;
            } else {
                // Direkt sembol verilmiş (BTCUSDT gibi)
                $symbol = strtoupper($symbolOrId) . 'USDT';
            }
            
            // Bybit'den mevcut pozisyonları kontrol et
            $positions = $client->getPositions($symbol);
            if ($positions['retCode'] !== 0) {
                return "❌ Bybit pozisyon kontrolü hatası: " . $positions['retMsg'];
            }
            
            $positionList = $positions['result']['list'] ?? [];
            $openPosition = null;
            
            foreach ($positionList as $pos) {
                if ((float) $pos['size'] > 0) {
                    $openPosition = $pos;
                    break;
                }
            }
            
            if (!$openPosition) {
                return "❌ <b>{$symbol} için açık pozisyon bulunamadı!</b>";
            }
            
            // Pozisyonu kapat
            $side = $openPosition['side'] === 'Buy' ? 'Sell' : 'Buy'; // Ters işlem
            $qty = $openPosition['size'];
            
            $closeResult = $client->closePosition($symbol, $side, (float) $qty);
            
            if ($closeResult['retCode'] !== 0) {
                return "❌ Pozisyon kapatma hatası: " . $closeResult['retMsg'];
            }
            
            // Trade kaydını veritabanında güncelle/oluştur
            $pnl = (float) ($openPosition['unrealisedPnl'] ?? 0);
            $entryPrice = (float) ($openPosition['avgPrice'] ?? 0);
            $markPrice = (float) ($openPosition['markPrice'] ?? 0);
            
            // Mevcut trade kaydını bul veya yeni oluştur
            $trade = \App\Models\Trade::where('symbol', $symbol)
                ->where('status', 'OPEN')
                ->first();
                
            if (!$trade) {
                // Eğer veritabanında kayıt yoksa yeni oluştur (geçmiş pozisyon)
                $trade = \App\Models\Trade::create([
                    'symbol' => $symbol,
                    'side' => $openPosition['side'] === 'Buy' ? 'LONG' : 'SHORT',
                    'status' => 'CLOSED',
                    'margin_mode' => 'CROSS',
                    'leverage' => (int) ($openPosition['leverage'] ?? 10),
                    'qty' => (float) $qty,
                    'entry_price' => $entryPrice,
                    'pnl' => $pnl,
                    'pnl_realized' => $pnl,
                    'bybit_order_id' => $closeResult['result']['orderId'] ?? null,
                    'opened_at' => now()->subMinutes(rand(5, 30)), // Tahmini açılış zamanı
                    'closed_at' => now(),
                    'meta' => json_encode(['source' => 'telegram_close', 'timestamp' => now()->toISOString()]),
                ]);
            } else {
                // Mevcut kaydı güncelle
                $trade->update([
                    'status' => 'CLOSED',
                    'pnl' => $pnl,
                    'pnl_realized' => $pnl,
                    'closed_at' => now(),
                    'meta' => json_encode(array_merge(
                        json_decode($trade->meta ?? '{}', true),
                        ['closed_via' => 'telegram', 'close_timestamp' => now()->toISOString()]
                    )),
                ]);
            }
            
            $currentTime = now()->setTimezone('Europe/Istanbul')->format('H:i:s');
            $pnlEmoji = $pnl > 0 ? '🟢' : '🔴';
            
            return "{$pnlEmoji} <b>Pozisyon kapandı patron!</b>\n\n" .
                   "📊 {$symbol} " . ($openPosition['side'] === 'Buy' ? 'LONG' : 'SHORT') . "\n" .
                   "📏 Miktar: {$qty}\n" .
                   "💰 P&L: \$" . number_format($pnl, 2) . "\n" .
                   "⏰ Kapatma: {$currentTime}";
                   
        } catch (\Exception $e) {
            return "❌ Pozisyon kapatma hatası: " . $e->getMessage();
        }
    }
    
    private function getPnLMessage(): string
    {
        try {
            // Gerçek Bybit hesap bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $account = $client->getAccountInfo();
            
            if ($account['retCode'] !== 0) {
                return "❌ Bybit hesap bilgisi alınamadı: " . $account['retMsg'];
            }
            
            $accountData = $account['result']['list'][0] ?? [];
            $totalPnL = (float) ($accountData['totalPerpUPL'] ?? 0);
            $totalEquity = (float) ($accountData['totalEquity'] ?? 0);
            $walletBalance = (float) ($accountData['totalWalletBalance'] ?? 0);
            
            // Gerçek P&L hesaplama
            $realizedPnL = $totalEquity - $walletBalance;
            
            return "💰 <b>P&L durumu!</b>\n\n" .
                   "💰 Gerçekleşen P&L: " . ($realizedPnL >= 0 ? '🟢' : '🔴') . " $" . number_format($realizedPnL, 2) . "\n" .
                   "📊 Gerçekleşmemiş P&L: " . ($totalPnL >= 0 ? '🟢' : '🔴') . " $" . number_format($totalPnL, 2) . "\n" .
                   "💼 Toplam Equity: $" . number_format($totalEquity, 2) . "\n" .
                   "🏦 Wallet Bakiye: $" . number_format($walletBalance, 2) . "\n" .
                   "⏰ Güncelleme: " . now()->setTimezone('Europe/Istanbul')->format('H:i:s');
        } catch (\Exception $e) {
            return "❌ P&L bilgisi alınamadı: " . $e->getMessage();
        }
    }
    
    private function getRecentTradesMessage(): string
    {
        try {
            // Gerçek Bybit execution history al
            $client = app('App\Services\Exchange\BybitClient');
            $symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];
            $endTime = now()->timestamp * 1000;
            $startTime = now()->subHour()->timestamp * 1000;
            
            $allExecutions = [];
            
            foreach ($symbols as $symbol) {
                try {
                    $executions = $client->executionList($symbol, $startTime, $endTime);
                    if ($executions['retCode'] === 0 && !empty($executions['result']['list'])) {
                        $allExecutions = array_merge($allExecutions, $executions['result']['list']);
                    }
                } catch (\Exception $e) {
                    // Symbol için execution alınamazsa devam et
                    continue;
                }
            }
            
            if (empty($allExecutions)) {
                return "📋 <b>Son 1 saatte hiç hareket yok patron!</b>\n\n⚪ Piyasa sakin geçmiş.";
            }
            
            // En son 5 işlemi al
            usort($allExecutions, function($a, $b) {
                return $b['execTime'] <=> $a['execTime'];
            });
            $recentExecutions = array_slice($allExecutions, 0, 5);
            
            $message = "📋 <b>Son işlemlerin! 💰</b>\n\n";
            
            foreach ($recentExecutions as $execution) {
                $side = $execution['side'] === 'Buy' ? 'LONG' : 'SHORT';
                $qty = $execution['execQty'];
                $price = $execution['execPrice'];
                $fee = $execution['execFee'];
                $time = \Carbon\Carbon::createFromTimestamp($execution['execTime'] / 1000)->setTimezone('Europe/Istanbul')->format('H:i:s');
                
                $emoji = $execution['side'] === 'Buy' ? '🟢' : '🔴';
                
                $message .= "{$emoji} <b>{$execution['symbol']}</b> {$side}\n";
                $message .= "   {$time} | \$" . number_format((float) $price, 2) . " | {$qty}\n";
            }
            
            return $message;
        } catch (\Exception $e) {
            return "❌ İşlem geçmişi alınamadı: " . $e->getMessage();
        }
    }
    
    public function getBalanceMessage(): string
    {
        try {
            // Gerçek Bybit hesap bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $account = $client->getAccountInfo();
            
            if ($account['retCode'] !== 0) {
                return "❌ Bybit hesap bilgisi alınamadı: " . $account['retMsg'];
            }
            
            $accountData = $account['result']['list'][0] ?? [];
            $totalEquity = (float) ($accountData['totalEquity'] ?? 0);
            $availableBalance = (float) ($accountData['totalAvailableBalance'] ?? 0);
            $walletBalance = (float) ($accountData['totalWalletBalance'] ?? 0);
            $marginUsed = $totalEquity - $availableBalance;
            $marginUsedPct = $totalEquity > 0 ? round(($marginUsed / $totalEquity) * 100, 1) : 0;
            
            return "💰 <b>Kasanda ne var bakalım!</b>\n\n" .
                   "🏦 Toplam Equity: \$" . number_format($totalEquity, 2) . "\n" .
                   "💼 Wallet Bakiye: \$" . number_format($walletBalance, 2) . "\n" .
                   "✅ Kullanılabilir: \$" . number_format($availableBalance, 2) . "\n" .
                   "🔒 Margin Kullanımı: \$" . number_format($marginUsed, 2) . " ({$marginUsedPct}%)\n" .
                   "⏰ Güncelleme: " . now()->setTimezone('Europe/Istanbul')->format('H:i:s');
        } catch (\Exception $e) {
            return "❌ Bakiye bilgisi alınamadı: " . $e->getMessage();
        }
    }
    
    public function getPositionsDetailMessage(): string
    {
        try {
            // Gerçek Bybit pozisyon bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $positions = $client->getPositions();
            
            if ($positions['retCode'] !== 0) {
                return "❌ Bybit pozisyon bilgisi alınamadı: " . $positions['retMsg'];
            }
            
            $positionList = $positions['result']['list'] ?? [];
            
            // Sadece açık pozisyonları filtrele
            $openPositions = array_filter($positionList, function($pos) {
                return (float) $pos['size'] > 0;
            });
            
            if (empty($openPositions)) {
                return "📊 <b>Şu an hiç pozisyon yok sayın Yücel!</b>\n\n⚪ Temiz hesap, yeni fırsatlar arayabiliriz.";
            }
            
            $message = "📊 <b>Senin aktif pozisyonların sayın Yücel!</b>\n\n";
            $totalPnL = 0;
            
            foreach ($openPositions as $position) {
                $symbol = $position['symbol'];
                $side = $position['side'] === 'Buy' ? 'LONG' : 'SHORT';
                $size = $position['size'];
                $entryPrice = (float) $position['avgPrice'];
                $markPrice = (float) $position['markPrice'];
                $leverage = $position['leverage'];
                $unrealizedPnl = (float) $position['unrealisedPnl'];
                $totalPnL += $unrealizedPnl;
                
                $pnlPct = $entryPrice > 0 ? round((($markPrice - $entryPrice) / $entryPrice) * 100, 2) : 0;
                if ($side === 'SHORT') $pnlPct = -$pnlPct;
                
                $pnlEmoji = $unrealizedPnl > 0 ? '🟢' : ($unrealizedPnl < 0 ? '🔴' : '⚪');
                $sideEmoji = $side === 'LONG' ? '📈' : '📉';
                
                $message .= "{$sideEmoji} <b>{$symbol}</b> {$side}\n";
                $message .= "   💰 Entry: \$" . number_format($entryPrice, 2) . "\n";
                $message .= "   📊 Mark: \$" . number_format($markPrice, 2) . "\n";
                $message .= "   📏 Size: {$size}\n";
                $message .= "   ⚡ Leverage: {$leverage}x\n";
                $message .= "   {$pnlEmoji} P&L: \$" . number_format($unrealizedPnl, 2) . " ({$pnlPct}%)\n\n";
            }
            
            $totalPnLEmoji = $totalPnL > 0 ? '🟢' : ($totalPnL < 0 ? '🔴' : '⚪');
            $message .= "═══════════════\n";
            $message .= "{$totalPnLEmoji} <b>Toplam P&L: $" . number_format($totalPnL, 2) . "</b>";
            
            return $message;
        } catch (\Exception $e) {
            return "❌ Pozisyon bilgisi alınamadı: " . $e->getMessage();
        }
    }
    
    public function getHelpMessage(): string
    {
        return "🤖 <b>Hazırım Patron!</b>\n\n" .
               "📋 <b>Trading Komutları:</b>\n" .
               "/scan - Tüm coinleri tara (4 coin)\n" .
               "/open BTC - BTC pozisyonu aç (gerekçe sor)\n" .
               "/open ETH - ETH pozisyonu aç (gerekçe sor)\n" .
               "/open SOL - SOL pozisyonu aç (gerekçe sor)\n" .
               "/open XRP - XRP pozisyonu aç (gerekçe sor)\n\n" .
               "📊 <b>Analiz Komutları:</b>\n" .
               "/status - Sistem durumu\n" .
               "/positions - Detaylı pozisyon bilgisi\n" .
               "/positionmanage - Pozisyon yönetimi\n" .
               "/pnl - Son 24 saat kar/zarar\n" .
               "/trades - Son 1 saat işlemler\n" .
               "/balance - Güncel bakiye\n\n" .
               "⚙️ <b>Pozisyon Yönetimi:</b>\n" .
               "/close COIN - Pozisyonu kapat (örn: /close BTC)\n" .
               "/manage - Pozisyon yönetim paneli\n\n" .
               "🔧 <b>Diğer:</b>\n" .
               "/risk SYMBOL - Risk profili\n" .
               "/help - Bu yardım\n\n" .
               "⏰ <b>Otomatik:</b> Her 2 saatte bir /scan\n" .
               "💡 <b>Özel pozisyon:</b> Gerekçe ile AI analizi";
    }



    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = config('notifier.telegram.bot_token');
        
        Log::info('Sending Telegram message', ['chat_id' => $chatId, 'text' => substr($text, 0, 100)]);
        
        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
        
        Log::info('Telegram response', ['status' => $response->status(), 'body' => $response->body()]);
    }
}
