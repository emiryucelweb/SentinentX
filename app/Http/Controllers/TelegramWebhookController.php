<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
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
        if (! $message) {
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
            try {
                // Use MultiCoinAnalysisService for real-time scanning
                $user = User::where('email', 'telegram@sentinentx.com')->first();
                if (!$user) {
                    $user = User::create([
                        'name' => 'Telegram User',
                        'email' => 'telegram@sentinentx.com',
                        'password' => bcrypt('telegram_user_' . time()),
                        'meta' => ['risk_profile' => 'moderate', 'source' => 'telegram']
                    ]);
                }
                $multiCoinService = app(\App\Services\AI\MultiCoinAnalysisService::class);
                $result = $multiCoinService->analyzeAllCoins($user, "Telegram scan request");
                
                $selectedCoin = $result['selected_coin'] ?? 'None';
                $success = $result['success'] ?? false;
                
                return "🔍 <b>4 Coin taraması tamamlandı!</b>\n\n" .
                       "📊 <b>Seçilen Coin:</b> {$selectedCoin}\n" .
                       "✅ <b>Durum:</b> " . ($success ? "Başarılı" : "Bekleme") . "\n\n" .
                       "Detaylı analiz için /positions komutunu kullan.";
            } catch (\Exception $e) {
                return "🔍 <b>Tarama başlatıldı!</b>\n\nTüm coinler analiz ediliyor...";
            }
        }

        if ($text === '/status') {
            return $this->getStatusMessage();
        }

        if (preg_match('/^\/open\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }

            // Desteklenen coinleri kontrol et
            $supportedCoins = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];
            if (! in_array($symbol, $supportedCoins)) {
                return "❌ <b>Desteklenmeyen coin!</b>\n\nDesteklenen coinler: BTC, ETH, SOL, XRP";
            }

            return "🎯 <b>{$symbol} pozisyonu açmak istiyorsun.</b>\n\n".
                   "⚡ <b>Risk tercihiniz nedir?</b>\n".
                   "🔹 <code>/risk1 {$symbol}</code> - Düşük Risk (3-15x, %5 pozisyon)\n".
                   "🔹 <code>/risk2 {$symbol}</code> - Orta Risk (15-45x, %10 pozisyon)\n".
                   "🔹 <code>/risk3 {$symbol}</code> - Yüksek Risk (45-125x, %15 pozisyon)";
        }

        if (preg_match('/^\/risk([123])\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $riskLevel = (int) $matches[1];
            $symbol = strtoupper($matches[2]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }

            $riskProfiles = [
                1 => ['name' => 'Düşük Risk', 'range' => '3-15x', 'min' => 3, 'max' => 15, 'pct' => 5],
                2 => ['name' => 'Orta Risk', 'range' => '15-45x', 'min' => 15, 'max' => 45, 'pct' => 10],
                3 => ['name' => 'Yüksek Risk', 'range' => '45-125x', 'min' => 45, 'max' => 125, 'pct' => 15],
            ];

            $profile = $riskProfiles[$riskLevel];

            return "⚡ <b>{$profile['name']} seçildi!</b>\n".
                   "📊 Kaldıraç: {$profile['range']}\n".
                   "💰 Pozisyon boyutu: %{$profile['pct']}\n\n".
                   "💭 <b>Neden bu pozisyonu açmak istiyorsun?</b>\n".
                   "Analiz gerekçeni yaz (haber, teknik analiz vs.)\n\n".
                   "Format: <code>/confirm {$symbol} {$riskLevel} gerekçen buraya</code>";
        }

        if (preg_match('/^\/confirm\s+([A-Z0-9]+)\s+([123])\s+(.+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }
            $riskLevel = (int) $matches[2];
            $userReason = trim($matches[3]);

            $riskProfiles = [
                1 => ['name' => 'Düşük Risk', 'min' => 3, 'max' => 15, 'pct' => 5],
                2 => ['name' => 'Orta Risk', 'min' => 15, 'max' => 45, 'pct' => 10],
                3 => ['name' => 'Yüksek Risk', 'min' => 45, 'max' => 125, 'pct' => 15],
            ];

            $profile = $riskProfiles[$riskLevel];

            // AI analizi başlat
            $aiDecisions = $this->getAIAnalysisWithRisk($symbol, $userReason, $profile);

            return "🤖 <b>AI Analizi Tamamlandı!</b>\n\n".
                   "📊 <b>{$symbol}</b> | {$profile['name']}\n".
                   "💭 <b>Gerekçe:</b> <i>{$userReason}</i>\n\n".
                   $aiDecisions."\n\n".
                   "✅ <code>/execute {$symbol}</code> - Pozisyonu aç\n".
                   '❌ <code>/cancel</code> - İptal et';
        }

        if (preg_match('/^\/execute\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }

            return $this->executePositionWithAI($symbol);
        }

        if ($text === '/cancel') {
            return "❌ <b>İşlem iptal edildi patron!</b>\n\n".
                   "Hiçbir pozisyon açılmadı. Para güvende! 💰\n\n".
                   '💡 Yeni pozisyon için /open komutunu kullan.';
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

        if (preg_match('/^\/detail\s+(\w+)$/i', $text, $matches)) {
            $coinSymbol = strtoupper($matches[1]);

            return $this->getPositionDetail($coinSymbol);
        }

        if (preg_match('/^\/close\s+(\w+)$/i', $text, $matches)) {
            $symbolOrId = $matches[1];

            return $this->closePosition($symbolOrId);
        }

        // Özel selamlama
        if (strtolower($text) === 'selam canım' || strtolower($text) === 'selam canim') {
            return "🤗 <b>Hoşgeldin patron!</b>\n\n".
                   "Bugün ne yapıyoruz? 💪\n\n".
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
                $client = app('App\Services\Exchange\BybitClient');
                $positions = $client->getPositions();
                if ($positions['retCode'] === 0) {
                    $openPositions = array_filter($positions['result']['list'] ?? [], function ($pos) {
                        return (float) $pos['size'] > 0;
                    });
                    $status['positions'] = count($openPositions).' açık pozisyon';
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
                'GROK_API_KEY' => 'Grok',
            ];

            foreach ($aiKeys as $envKey => $name) {
                $aiStatus[$name] = ! empty(config('app.'.strtolower($name).'_api_key', env($envKey))) ? '🟢' : '🔴';
            }

            $currentTime = now()->setTimezone('Europe/Istanbul')->format('H:i:s');

            return "🤖 <b>Durum raporu!</b>\n\n".
                   "🏛️ <b>Bybit bağlantısı:</b> {$status['bybit']}\n".
                   "📊 <b>Aktif pozisyonların:</b> {$status['positions']}\n".
                   "🔴 <b>Redis durumu:</b> {$status['redis']}\n\n".
                   "🧠 <b>AI ekibim:</b>\n".
                   "• OpenAI: {$aiStatus['OpenAI']}\n".
                   "• Gemini: {$aiStatus['Gemini']}\n".
                   "• Grok: {$aiStatus['Grok']}\n\n".
                   '⚙️ <b>Çalışma ortamı:</b> '.(config('app.env') === 'testnet' ? 'Testnet (güvenli) 🛡️' : 'Production ⚡')."\n".
                   "⏰ <b>Son kontrol:</b> {$currentTime}";

        } catch (\Exception $e) {
            return '❌ Sistem durumu alınamadı: '.$e->getMessage();
        }
    }

    private function getAIAnalysisWithRisk(string $symbol, string $userReason, array $riskProfile): string
    {
        try {
            // AI servisleri kullanarak gerçek analiz yap
            $consensusService = app(\App\Services\AI\ConsensusService::class);

            // Gerçek market data'sını al
            $marketData = $this->getRealMarketData($symbol);
            $portfolioData = $this->getRealPortfolioData();

            // Risk profili ile snapshot oluştur
            $snapshot = [
                'symbol' => $symbol,
                'symbols' => [$symbol],
                'timestamp' => now()->toISOString(),
                'user_intent' => [
                    'reason' => $userReason,
                    'request_type' => 'specific_position',
                    'timestamp' => now()->toISOString(),
                ],
                'market_data' => $marketData,
                'portfolio' => $portfolioData,
                'risk_context' => [
                    'risk_profile' => $riskProfile['name'],
                    'min_leverage' => $riskProfile['min'],
                    'max_leverage' => $riskProfile['max'],
                    'position_size_pct' => $riskProfile['pct'],
                ],
            ];

            // AI kararlarını al
            $decision = $consensusService->decide($snapshot);

            // AI kararlarını formatla
            $message = "🧠 <b>AI Ekibimin Kararları:</b>\n\n";

            if (isset($decision['stage1_results'])) {
                foreach ($decision['stage1_results'] as $provider => $result) {
                    $action = $result['action'] ?? 'HOLD';
                    $confidence = $result['confidence'] ?? 0;
                    $leverage = $result['leverage'] ?? 10;
                    $reason = $result['reasoning'] ?? 'Analiz yapıldı';

                    $actionEmoji = $action === 'BUY' ? '🟢' : ($action === 'SELL' ? '🔴' : '🟡');

                    $message .= "🤖 <b>{$provider}:</b>\n";
                    $message .= "   {$actionEmoji} {$action} | Güven: %{$confidence} | Kaldıraç: {$leverage}x\n";
                    $message .= '   💭 <i>'.substr($reason, 0, 80)."...</i>\n\n";
                }
            }

            // Consensus sonucu
            $finalAction = $decision['final_decision']['action'] ?? 'HOLD';
            $finalConfidence = $decision['final_decision']['confidence'] ?? 0;
            $finalLeverage = $decision['average_leverage'] ?? 10;

            $finalEmoji = $finalAction === 'BUY' ? '🟢' : ($finalAction === 'SELL' ? '🔴' : '🟡');

            $message .= "🎯 <b>Final Karar:</b>\n";
            $message .= "{$finalEmoji} <b>{$finalAction}</b> | Güven: <b>%{$finalConfidence}</b> | Kaldıraç: <b>{$finalLeverage}x</b>\n";

            // Pozisyon detayları
            if ($finalAction !== 'HOLD') {
                $message .= "\n💰 <b>Pozisyon Detayları:</b>\n";
                $message .= "📊 Sembol: {$symbol}\n";
                $message .= "⚡ Kaldıraç: {$finalLeverage}x\n";
                $message .= "🎯 Risk: {$riskProfile['name']} (%{$riskProfile['pct']} portföy)\n";
            }

            return $message;

        } catch (\Exception $e) {
            // Hata durumunda veritabanından son AI kararlarını al
            try {
                $latestDecision = \App\Models\ConsensusDecision::where('symbol', $symbol)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestDecision) {
                    $aiLogs = \App\Models\AiLog::where('consensus_decision_id', $latestDecision->id)
                        ->get();

                    $message = "🤖 <b>AI Ekibimin Son Kararları:</b>\n\n";

                    foreach ($aiLogs as $log) {
                        $action = strtoupper($log->decision['action'] ?? 'HOLD');
                        $confidence = $log->decision['confidence'] ?? 0;
                        $leverage = $log->decision['leverage'] ?? 10;
                        $reasoning = $log->decision['reasoning'] ?? 'Analiz yapıldı';

                        $actionEmoji = $action === 'BUY' ? '🟢' : ($action === 'SELL' ? '🔴' : '🟡');

                        $message .= "🤖 <b>{$log->provider}:</b>\n";
                        $message .= "   {$actionEmoji} {$action} | Güven: %{$confidence} | Kaldıraç: {$leverage}x\n";
                        $message .= '   💭 <i>'.substr($reasoning, 0, 80)."...</i>\n\n";
                    }

                    // Final karar
                    $finalAction = strtoupper($latestDecision->final_decision['action'] ?? 'HOLD');
                    $finalConfidence = $latestDecision->final_decision['confidence'] ?? 0;
                    $finalLeverage = $latestDecision->average_leverage ?? 10;

                    $finalEmoji = $finalAction === 'BUY' ? '🟢' : ($finalAction === 'SELL' ? '🔴' : '🟡');

                    $message .= "🎯 <b>Final Karar:</b>\n";
                    $message .= "{$finalEmoji} <b>{$finalAction}</b> | Güven: <b>%{$finalConfidence}</b> | Kaldıraç: <b>{$finalLeverage}x</b>\n\n";

                    $message .= "💰 <b>Pozisyon Detayları:</b>\n";
                    $message .= "📊 Sembol: {$symbol}\n";
                    $message .= "⚡ Kaldıraç: {$finalLeverage}x\n";
                    $message .= "🎯 Risk: {$riskProfile['name']} (%{$riskProfile['pct']} portföy)";

                    return $message;
                }
            } catch (\Exception $dbE) {
                // Veritabanından da veri alınamazsa
            }

            return "❌ <b>AI analizi şu anda kullanılamıyor</b>\n\n".
                   "Sistem geçici olarak bakımda. Lütfen daha sonra tekrar deneyin.\n\n".
                   '💡 Mevcut pozisyonlar için /manage komutunu kullanabilirsin.';
        }
    }

    private function executePositionWithAI(string $symbol): string
    {
        try {
            // Gerçek pozisyon açma işlemi
            $client = app(\App\Services\Exchange\BybitClient::class);

            // Son AI kararına göre pozisyon aç (burada basitleştirilmiş)
            $result = $client->createOrder(
                $symbol,
                'Buy',
                'Market',
                0.01,
                null, // price
                [
                    'category' => 'linear',
                    'timeInForce' => 'IOC',
                    'orderLinkId' => 'tg_' . uniqid() . '_' . time()
                ]
            );

            if (isset($result['retCode']) && $result['retCode'] === 0) {
                $orderId = $result['result']['orderId'] ?? 'N/A';

                return "🎉 <b>Pozisyon Başarıyla Açıldı!</b>\n\n".
                       "📊 <b>Sembol:</b> {$symbol}\n".
                       "🟢 <b>Yön:</b> LONG\n".
                       "⚡ <b>Kaldıraç:</b> 12x\n".
                       "💰 <b>Miktar:</b> 0.01 {$symbol}\n".
                       "🆔 <b>Order ID:</b> {$orderId}\n".
                       '⏰ <b>Zaman:</b> '.now()->setTimezone('Europe/Istanbul')->format('H:i:s')."\n\n".
                       '💡 Pozisyon detayları için /positions komutunu kullan';
            } else {
                return "❌ <b>Pozisyon açılamadı!</b>\n\n".
                       'Hata: '.($result['retMsg'] ?? 'Bilinmeyen hata');
            }

        } catch (\Exception $e) {
            return "❌ <b>Pozisyon açma hatası:</b>\n\n".$e->getMessage();
        }
    }

    private function getPositionManageMessage(): string
    {
        try {
            // Gerçek Bybit pozisyon bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $positions = $client->getPositions();

            if ($positions['retCode'] !== 0) {
                return '❌ Bybit pozisyon bilgisi alınamadı: '.$positions['retMsg'];
            }

            $positionList = $positions['result']['list'] ?? [];

            // Sadece açık pozisyonları filtrele
            $openPositions = array_filter($positionList, function ($pos) {
                return (float) $pos['size'] > 0;
            });

            if (empty($openPositions)) {
                return "📊 <b>Pozisyon Yönetimi</b>\n\n⚪ Hiç açık pozisyon yok.\n\n💡 Yeni pozisyon açmak için /open komutunu kullan.";
            }

            $message = "📊 <b>Pozisyon Yönetimi</b>\n\n";
            $totalPnl = 0;

            foreach ($openPositions as $index => $position) {
                $symbol = $position['symbol'];
                $side = $position['side'] === 'Buy' ? 'LONG' : 'SHORT';
                $size = $position['size'];
                $entryPrice = (float) $position['avgPrice'];
                $markPrice = (float) $position['markPrice'];
                $leverage = $position['leverage'];
                $unrealizedPnl = (float) $position['unrealisedPnl'];
                $totalPnl += $unrealizedPnl;

                // Pozisyon açılma zamanını hesapla (timestamp varsa)
                $createdTime = $position['createdTime'] ?? null;
                $timeAgo = '';
                if ($createdTime) {
                    $openTime = \Carbon\Carbon::createFromTimestamp($createdTime / 1000);
                    $timeAgo = $openTime->setTimezone('Europe/Istanbul')->diffForHumans();
                }

                $pnlPct = $entryPrice > 0 ? round((($markPrice - $entryPrice) / $entryPrice) * 100, 2) : 0;
                if ($side === 'SHORT') {
                    $pnlPct = -$pnlPct;
                }

                $pnlEmoji = $unrealizedPnl > 0 ? '🟢' : ($unrealizedPnl < 0 ? '🔴' : '⚪');
                $sideEmoji = $side === 'LONG' ? '📈' : '📉';

                // Symbol'den sadece coin kısmını al (BTCUSDT -> BTC)
                $coinSymbol = str_replace('USDT', '', $symbol);

                $positionNum = $index + 1;
                $message .= "#{$positionNum} {$sideEmoji} <b>{$symbol}</b> {$side}\n";
                $message .= '   💰 <b>Entry:</b> $'.number_format($entryPrice, 2)."\n";
                $message .= '   📊 <b>Mark:</b> $'.number_format($markPrice, 2)."\n";
                $message .= "   📏 <b>Size:</b> {$size} | ⚡ <b>Leverage:</b> {$leverage}x\n";
                $message .= "   {$pnlEmoji} <b>P&L:</b> \$".number_format($unrealizedPnl, 2)." (<b>{$pnlPct}%</b>)\n";

                if ($timeAgo) {
                    $message .= "   ⏰ <b>Açılma:</b> {$timeAgo}\n";
                }

                // SL/TP bilgileri (varsa)
                $stopLoss = $position['stopLoss'] ?? null;
                $takeProfit = $position['takeProfit'] ?? null;

                if ($stopLoss && $stopLoss != '0') {
                    $message .= '   🛡️ <b>SL:</b> $'.number_format((float) $stopLoss, 2)."\n";
                }
                if ($takeProfit && $takeProfit != '0') {
                    $message .= '   🏆 <b>TP:</b> $'.number_format((float) $takeProfit, 2)."\n";
                }

                $message .= "   🔧 <code>/detail {$coinSymbol}</code> - Detayları gör\n";
                $message .= "   🗂 <code>/close {$coinSymbol}</code> - Pozisyonu kapat\n\n";
            }

            // Toplam P&L
            $totalPnlEmoji = $totalPnl > 0 ? '🟢' : ($totalPnl < 0 ? '🔴' : '⚪');
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "{$totalPnlEmoji} <b>Toplam P&L:</b> \$".number_format($totalPnl, 2)."\n\n";

            $message .= "💡 <b>Komutlar:</b>\n";
            $message .= "• <code>/detail COIN</code> - Pozisyon detayları\n";
            $message .= "• <code>/close COIN</code> - Pozisyonu kapat\n";
            $message .= "• <code>/positions</code> - Tüm pozisyonlar\n";
            $message .= '• <code>/balance</code> - Bakiye bilgisi';

            return $message;
        } catch (\Exception $e) {
            return '❌ Pozisyon yönetimi hatası: '.$e->getMessage();
        }
    }

    private function getRealMarketData(string $symbol): array
    {
        try {
            $client = app('App\Services\Exchange\BybitClient');

            // Ticker bilgisini al
            $ticker = $client->tickers($symbol);

            if ($ticker['retCode'] === 0 && ! empty($ticker['result']['list'])) {
                $tickerData = $ticker['result']['list'][0];

                return [
                    $symbol => [
                        'price' => (float) $tickerData['lastPrice'],
                        'change_24h' => (float) $tickerData['price24hPcnt'] * 100,
                        'volume_24h' => (float) $tickerData['volume24h'],
                        'high_24h' => (float) $tickerData['highPrice24h'],
                        'low_24h' => (float) $tickerData['lowPrice24h'],
                    ],
                ];
            }
        } catch (\Exception $e) {
            // Hata durumunda boş array döndür
        }

        return [$symbol => ['price' => 0, 'change_24h' => 0]];
    }

    private function getRealPortfolioData(): array
    {
        try {
            $client = app('App\Services\Exchange\BybitClient');

            // Hesap bilgisini al
            $account = $client->getAccountInfo();

            if ($account['retCode'] === 0 && ! empty($account['result']['list'])) {
                $accountData = $account['result']['list'][0];

                return [
                    'total_balance' => (float) $accountData['totalEquity'],
                    'available_balance' => (float) $accountData['totalAvailableBalance'],
                    'unrealized_pnl' => (float) $accountData['totalPerpUPL'],
                    'margin_ratio' => (float) $accountData['accountIMRate'],
                ];
            }
        } catch (\Exception $e) {
            // Hata durumunda varsayılan değerler
        }

        return [
            'total_balance' => 10000,
            'available_balance' => 9500,
            'unrealized_pnl' => 0,
            'margin_ratio' => 0,
        ];
    }

    private function getPositionDetail(string $coinSymbol): string
    {
        try {
            $symbol = $coinSymbol.'USDT';
            $client = app('App\Services\Exchange\BybitClient');

            // Spesifik sembol için pozisyon bilgisi al
            $positions = $client->getPositions($symbol);

            if ($positions['retCode'] !== 0) {
                return '❌ Pozisyon bilgisi alınamadı: '.$positions['retMsg'];
            }

            $positionList = $positions['result']['list'] ?? [];
            $openPosition = null;

            foreach ($positionList as $pos) {
                if ((float) $pos['size'] > 0) {
                    $openPosition = $pos;
                    break;
                }
            }

            if (! $openPosition) {
                return "❌ <b>{$symbol} için açık pozisyon bulunamadı!</b>\n\n💡 /manage komutunu kullanarak tüm pozisyonları görebilirsin.";
            }

            // Pozisyon detaylarını parse et
            $side = $openPosition['side'] === 'Buy' ? 'LONG' : 'SHORT';
            $size = $openPosition['size'];
            $entryPrice = (float) $openPosition['avgPrice'];
            $markPrice = (float) $openPosition['markPrice'];
            $leverage = $openPosition['leverage'];
            $unrealizedPnl = (float) $openPosition['unrealisedPnl'];
            $unrealizedPnlPct = (float) $openPosition['unrealisedPnlPct'];

            // Ek bilgiler
            $positionValue = (float) $openPosition['positionValue'];
            $initialMargin = (float) $openPosition['positionIM'];
            $maintMargin = (float) $openPosition['positionMM'];

            // SL/TP bilgileri
            $stopLoss = $openPosition['stopLoss'] ?? '0';
            $takeProfit = $openPosition['takeProfit'] ?? '0';

            // Pozisyon açılma zamanı
            $createdTime = $openPosition['createdTime'] ?? null;
            $openTime = null;
            $timeAgo = '';
            if ($createdTime) {
                $openTime = \Carbon\Carbon::createFromTimestamp($createdTime / 1000);
                $timeAgo = $openTime->setTimezone('Europe/Istanbul')->diffForHumans();
                $openTimeFormatted = $openTime->setTimezone('Europe/Istanbul')->format('d.m.Y H:i:s');
            }

            // P&L hesaplamaları
            $pnlPct = $entryPrice > 0 ? round((($markPrice - $entryPrice) / $entryPrice) * 100, 2) : 0;
            if ($side === 'SHORT') {
                $pnlPct = -$pnlPct;
            }

            $pnlEmoji = $unrealizedPnl > 0 ? '🟢' : ($unrealizedPnl < 0 ? '🔴' : '⚪');
            $sideEmoji = $side === 'LONG' ? '📈' : '📉';

            // Liquidation price
            $liqPrice = (float) ($openPosition['liqPrice'] ?? 0);

            // Risk hesaplaması (current price vs liquidation)
            $riskPct = 0;
            if ($liqPrice > 0) {
                if ($side === 'LONG') {
                    $riskPct = round((($markPrice - $liqPrice) / $markPrice) * 100, 2);
                } else {
                    $riskPct = round((($liqPrice - $markPrice) / $markPrice) * 100, 2);
                }
            }

            // Detaylı mesaj oluştur
            $message = "📊 <b>Pozisyon Detayları</b>\n\n";
            $message .= "{$sideEmoji} <b>{$symbol}</b> {$side}\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            // Temel bilgiler
            $message .= '💰 <b>Entry Price:</b> $'.number_format($entryPrice, 2)."\n";
            $message .= '📊 <b>Mark Price:</b> $'.number_format($markPrice, 2)."\n";
            $message .= "📏 <b>Position Size:</b> {$size}\n";
            $message .= "⚡ <b>Leverage:</b> {$leverage}x\n";
            $message .= '💵 <b>Position Value:</b> $'.number_format($positionValue, 2)."\n\n";

            // P&L bilgileri
            $message .= "📈 <b>P&L Bilgileri:</b>\n";
            $message .= "{$pnlEmoji} <b>Unrealized P&L:</b> \$".number_format($unrealizedPnl, 2)." (<b>{$pnlPct}%</b>)\n";
            $message .= '💎 <b>Initial Margin:</b> $'.number_format($initialMargin, 2)."\n";
            $message .= '🛡️ <b>Maintenance Margin:</b> $'.number_format($maintMargin, 2)."\n\n";

            // Risk bilgileri
            if ($liqPrice > 0) {
                $message .= "⚠️ <b>Risk Bilgileri:</b>\n";
                $message .= '💥 <b>Liquidation Price:</b> $'.number_format($liqPrice, 2)."\n";
                $message .= "📊 <b>Risk Mesafesi:</b> {$riskPct}%\n\n";
            }

            // SL/TP bilgileri
            if ($stopLoss != '0' || $takeProfit != '0') {
                $message .= "🎯 <b>Stop Loss / Take Profit:</b>\n";
                if ($stopLoss != '0') {
                    $message .= '🛡️ <b>Stop Loss:</b> $'.number_format((float) $stopLoss, 2)."\n";
                }
                if ($takeProfit != '0') {
                    $message .= '🏆 <b>Take Profit:</b> $'.number_format((float) $takeProfit, 2)."\n";
                }
                $message .= "\n";
            }

            // Zaman bilgileri
            if ($openTime) {
                $message .= "⏰ <b>Zaman Bilgileri:</b>\n";
                $message .= "📅 <b>Açılma:</b> {$openTimeFormatted}\n";
                $message .= "⏳ <b>Süre:</b> {$timeAgo}\n\n";
            }

            // AI kararı bilgisi (varsa)
            try {
                $trade = \App\Models\Trade::where('symbol', $symbol)
                    ->where('status', 'open')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($trade) {
                    $message .= "🤖 <b>AI Kararı:</b>\n";
                    $message .= '🎯 <b>Consensus:</b> '.strtoupper($trade->direction ?? 'N/A')."\n";
                    $message .= '🔮 <b>Confidence:</b> '.($trade->confidence ?? 'N/A')."%\n";
                    if ($trade->user_reason) {
                        $message .= '💭 <b>Gerekçe:</b> <i>'.substr($trade->user_reason, 0, 100)."...</i>\n";
                    }
                    $message .= "\n";
                }
            } catch (\Exception $e) {
                // AI bilgisi alınamazsa sessizce geç
            }

            // Aksiyon butonları
            $message .= "🔧 <b>Aksiyonlar:</b>\n";
            $message .= "• <code>/close {$coinSymbol}</code> - Pozisyonu kapat\n";
            $message .= "• <code>/manage</code> - Tüm pozisyonlar\n";
            $message .= '• <code>/balance</code> - Bakiye durumu';

            return $message;

        } catch (\Exception $e) {
            return '❌ Pozisyon detayı alınamadı: '.$e->getMessage();
        }
    }

    public function closePosition(string $symbolOrId): string
    {
        try {
            $client = app('App\Services\Exchange\BybitClient');

            // Eğer sayısal ID ise, lokal veritabanından sembole çevir
            if (is_numeric($symbolOrId)) {
                $trade = \App\Models\Trade::find($symbolOrId);
                if (! $trade) {
                    return "❌ <b>Pozisyon bulunamadı!</b>\n\nID: {$symbolOrId}";
                }
                $symbol = $trade->symbol;
            } else {
                // Direkt sembol verilmiş (BTC gibi)
                $symbol = strtoupper($symbolOrId);
                if (! str_ends_with($symbol, 'USDT')) {
                    $symbol .= 'USDT';
                }
            }

            // Bybit'den mevcut pozisyonları kontrol et
            $positions = $client->getPositions($symbol);
            if ($positions['retCode'] !== 0) {
                return '❌ Bybit pozisyon kontrolü hatası: '.$positions['retMsg'];
            }

            $positionList = $positions['result']['list'] ?? [];
            $openPosition = null;

            foreach ($positionList as $pos) {
                if ((float) $pos['size'] > 0) {
                    $openPosition = $pos;
                    break;
                }
            }

            if (! $openPosition) {
                return "❌ <b>{$symbol} için açık pozisyon bulunamadı!</b>";
            }

            // Pozisyonu kapat
            $side = $openPosition['side'] === 'Buy' ? 'Sell' : 'Buy'; // Ters işlem
            $qty = $openPosition['size'];

            $closeResult = $client->closePosition($symbol, $side, (float) $qty);

            if ($closeResult['retCode'] !== 0) {
                return '❌ Pozisyon kapatma hatası: '.$closeResult['retMsg'];
            }

            // Trade kaydını veritabanında güncelle/oluştur
            $pnl = (float) ($openPosition['unrealisedPnl'] ?? 0);
            $entryPrice = (float) ($openPosition['avgPrice'] ?? 0);
            $markPrice = (float) ($openPosition['markPrice'] ?? 0);

            // Mevcut trade kaydını bul veya yeni oluştur
            $trade = \App\Models\Trade::where('symbol', $symbol)
                ->where('status', 'OPEN')
                ->first();

            if (! $trade) {
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

            return "{$pnlEmoji} <b>Pozisyon kapandı patron!</b>\n\n".
                   "📊 {$symbol} ".($openPosition['side'] === 'Buy' ? 'LONG' : 'SHORT')."\n".
                   "📏 Miktar: {$qty}\n".
                   '💰 P&L: $'.number_format($pnl, 2)."\n".
                   "⏰ Kapatma: {$currentTime}";

        } catch (\Exception $e) {
            return '❌ Pozisyon kapatma hatası: '.$e->getMessage();
        }
    }

    private function getPnLMessage(): string
    {
        try {
            // Gerçek Bybit hesap bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $account = $client->getAccountInfo();

            if ($account['retCode'] !== 0) {
                return '❌ Bybit hesap bilgisi alınamadı: '.$account['retMsg'];
            }

            $accountData = $account['result']['list'][0] ?? [];
            $totalPnL = (float) ($accountData['totalPerpUPL'] ?? 0);
            $totalEquity = (float) ($accountData['totalEquity'] ?? 0);
            $walletBalance = (float) ($accountData['totalWalletBalance'] ?? 0);

            // Gerçek P&L hesaplama
            $realizedPnL = $totalEquity - $walletBalance;

            return "💰 <b>P&L durumu!</b>\n\n".
                   '💰 Gerçekleşen P&L: '.($realizedPnL >= 0 ? '🟢' : '🔴').' $'.number_format($realizedPnL, 2)."\n".
                   '📊 Gerçekleşmemiş P&L: '.($totalPnL >= 0 ? '🟢' : '🔴').' $'.number_format($totalPnL, 2)."\n".
                   '💼 Toplam Equity: $'.number_format($totalEquity, 2)."\n".
                   '🏦 Wallet Bakiye: $'.number_format($walletBalance, 2)."\n".
                   '⏰ Güncelleme: '.now()->setTimezone('Europe/Istanbul')->format('H:i:s');
        } catch (\Exception $e) {
            return '❌ P&L bilgisi alınamadı: '.$e->getMessage();
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
                    if ($executions['retCode'] === 0 && ! empty($executions['result']['list'])) {
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
            usort($allExecutions, function ($a, $b) {
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
                $message .= "   {$time} | \$".number_format((float) $price, 2)." | {$qty}\n";
            }

            return $message;
        } catch (\Exception $e) {
            return '❌ İşlem geçmişi alınamadı: '.$e->getMessage();
        }
    }

    public function getBalanceMessage(): string
    {
        try {
            // Gerçek Bybit hesap bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $account = $client->getAccountInfo();

            if ($account['retCode'] !== 0) {
                return '❌ Bybit hesap bilgisi alınamadı: '.$account['retMsg'];
            }

            $accountData = $account['result']['list'][0] ?? [];
            $totalEquity = (float) ($accountData['totalEquity'] ?? 0);
            $availableBalance = (float) ($accountData['totalAvailableBalance'] ?? 0);
            $walletBalance = (float) ($accountData['totalWalletBalance'] ?? 0);
            $marginUsed = $totalEquity - $availableBalance;
            $marginUsedPct = $totalEquity > 0 ? round(($marginUsed / $totalEquity) * 100, 1) : 0;

            return "💰 <b>Kasanda ne var bakalım!</b>\n\n".
                   '🏦 Toplam Equity: $'.number_format($totalEquity, 2)."\n".
                   '💼 Wallet Bakiye: $'.number_format($walletBalance, 2)."\n".
                   '✅ Kullanılabilir: $'.number_format($availableBalance, 2)."\n".
                   '🔒 Margin Kullanımı: $'.number_format($marginUsed, 2)." ({$marginUsedPct}%)\n".
                   '⏰ Güncelleme: '.now()->setTimezone('Europe/Istanbul')->format('H:i:s');
        } catch (\Exception $e) {
            return '❌ Bakiye bilgisi alınamadı: '.$e->getMessage();
        }
    }

    public function getPositionsDetailMessage(): string
    {
        try {
            // Gerçek Bybit pozisyon bilgilerini al
            $client = app('App\Services\Exchange\BybitClient');
            $positions = $client->getPositions();

            if ($positions['retCode'] !== 0) {
                return '❌ Bybit pozisyon bilgisi alınamadı: '.$positions['retMsg'];
            }

            $positionList = $positions['result']['list'] ?? [];

            // Sadece açık pozisyonları filtrele
            $openPositions = array_filter($positionList, function ($pos) {
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
                if ($side === 'SHORT') {
                    $pnlPct = -$pnlPct;
                }

                $pnlEmoji = $unrealizedPnl > 0 ? '🟢' : ($unrealizedPnl < 0 ? '🔴' : '⚪');
                $sideEmoji = $side === 'LONG' ? '📈' : '📉';

                $message .= "{$sideEmoji} <b>{$symbol}</b> {$side}\n";
                $message .= '   💰 Entry: $'.number_format($entryPrice, 2)."\n";
                $message .= '   📊 Mark: $'.number_format($markPrice, 2)."\n";
                $message .= "   📏 Size: {$size}\n";
                $message .= "   ⚡ Leverage: {$leverage}x\n";
                $message .= "   {$pnlEmoji} P&L: \$".number_format($unrealizedPnl, 2)." ({$pnlPct}%)\n\n";
            }

            $totalPnLEmoji = $totalPnL > 0 ? '🟢' : ($totalPnL < 0 ? '🔴' : '⚪');
            $message .= "═══════════════\n";
            $message .= "{$totalPnLEmoji} <b>Toplam P&L: $".number_format($totalPnL, 2).'</b>';

            return $message;
        } catch (\Exception $e) {
            return '❌ Pozisyon bilgisi alınamadı: '.$e->getMessage();
        }
    }

    public function getHelpMessage(): string
    {
        return "🤖 <b>Hazırım Patron!</b>\n\n".
               "📋 <b>Trading Komutları:</b>\n".
               "/scan - Tüm coinleri tara (4 coin)\n".
               "/open BTC - BTC pozisyonu aç (gerekçe sor)\n".
               "/open ETH - ETH pozisyonu aç (gerekçe sor)\n".
               "/open SOL - SOL pozisyonu aç (gerekçe sor)\n".
               "/open XRP - XRP pozisyonu aç (gerekçe sor)\n\n".
               "📊 <b>Analiz Komutları:</b>\n".
               "/status - Sistem durumu\n".
               "/positions - Detaylı pozisyon bilgisi\n".
               "/positionmanage - Pozisyon yönetimi\n".
               "/pnl - Son 24 saat kar/zarar\n".
               "/trades - Son 1 saat işlemler\n".
               "/balance - Güncel bakiye\n\n".
               "⚙️ <b>Pozisyon Yönetimi:</b>\n".
               "/close COIN - Pozisyonu kapat (örn: /close BTC)\n".
               "/manage - Pozisyon yönetim paneli\n\n".
               "🔧 <b>Diğer:</b>\n".
               "/risk SYMBOL - Risk profili\n".
               "/help - Bu yardım\n\n".
               "⏰ <b>Otomatik Trading:</b> Her 2 saatte bir yeni pozisyon arama\n".
               "🔄 <b>Pozisyon İzleme:</b> Risk profiline göre 1-3 dakikada bir AI kontrolü\n".
               '💡 <b>Özel pozisyon:</b> Gerekçe ile AI analizi';
    }

    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = config('notifier.telegram.bot_token');

        Log::info('Sending Telegram message', ['chat_id' => $chatId, 'text' => substr($text, 0, 100)]);

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        Log::info('Telegram response', ['status' => $response->status(), 'body' => $response->body()]);
    }
}
