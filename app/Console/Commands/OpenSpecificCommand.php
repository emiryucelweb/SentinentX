<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\ConsensusService;
use Illuminate\Console\Command;

final class OpenSpecificCommand extends Command
{
    protected $signature = 'sentx:open-specific 
        {target_symbol : Hedef coin (pozisyon açılacak)}
        {--snapshot= : Path to snapshot JSON file}
        {--dry : Dry run mode - no actual trades}';

    protected $description = 'Tüm 4 coini analiz et ama sadece belirtilen coinde pozisyon aç';

    public function handle(ConsensusService $consensus): int
    {
        $targetSymbol = strtoupper((string) $this->argument('target_symbol'));
        $isDryRun = (bool) $this->option('dry');

        $this->info("🎯 Hedef Symbol: {$targetSymbol}");
        $this->info("📊 4 coin analiz edilecek, sadece {$targetSymbol} için pozisyon açılacak");

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No actual trades will be executed');
        }

        $path = (string) $this->option('snapshot');
        if (! $path || ! is_file($path)) {
            $this->error('--snapshot=/path/to/snapshot.json zorunlu');

            return self::FAILURE;
        }

        $snap = json_decode((string) file_get_contents($path), true);
        if (! is_array($snap)) {
            $this->error('Snapshot JSON okunamadı');

            return self::FAILURE;
        }

        // Snapshot validation
        if (! isset($snap['timestamp'], $snap['symbols'], $snap['market_data'])) {
            $this->error('Snapshot schema validation failed: missing required fields');

            return self::FAILURE;
        }

        $allSymbols = $snap['symbols']; // 4 coin: BTCUSDT, ETHUSDT, SOLUSDT, XRPUSDT
        $snap['dry_run'] = $isDryRun;
        $snap['mode'] = config('trading.mode');
        $snap['risk'] = config('trading.risk');

        $this->line('');
        $this->info('🤖 AI Consensus başlatılıyor...');
        $this->line('📋 Analiz edilecek coinler: '.implode(', ', $allSymbols));
        $this->line('🎯 Pozisyon açılacak coin: '.$targetSymbol);
        $this->line('');

        // Tüm coinler için analiz yap
        $allResults = [];
        foreach ($allSymbols as $symbol) {
            $this->line("📈 {$symbol} analiz ediliyor...");

            // Her coin için ayrı snapshot
            $symbolSnap = $snap;
            $symbolSnap['current_symbol'] = $symbol;
            $symbolSnap['symbols'] = [$symbol]; // Consensus service tek symbol bekliyor

            try {
                $result = $consensus->decide($symbolSnap);
                $allResults[$symbol] = $result;

                $action = $result['action'] ?? 'NO_TRADE';
                $confidence = $result['confidence'] ?? 0;
                $leverage = $result['leverage'] ?? 1;

                $emoji = match ($action) {
                    'LONG' => '🟢',
                    'SHORT' => '🔴',
                    default => '⚪'
                };

                $this->line("   {$emoji} {$symbol}: {$action} (Confidence: {$confidence}%, Leverage: {$leverage}x)");

            } catch (\Exception $e) {
                $this->error("   ❌ {$symbol}: ".$e->getMessage());
                $allResults[$symbol] = ['action' => 'ERROR', 'error' => $e->getMessage()];
            }
        }

        $this->line('');
        $this->info('📊 Tüm analizler tamamlandı!');

        // Hedef coin sonucunu kontrol et
        if (! isset($allResults[$targetSymbol])) {
            $this->error("❌ {$targetSymbol} analizi başarısız!");

            return self::FAILURE;
        }

        $targetResult = $allResults[$targetSymbol];
        $targetAction = $targetResult['action'] ?? 'NO_TRADE';

        if ($targetAction === 'NO_TRADE' || $targetAction === 'ERROR') {
            $this->warn("⚠️ {$targetSymbol} için pozisyon açılmayacak: {$targetAction}");
            $reason = $targetResult['reason'] ?? 'Bilinmeyen sebep';
            $this->line("📝 Sebep: {$reason}");

            return self::SUCCESS;
        }

        // Pozisyon açma simülasyonu (gerçek implementasyon için TradeManager gerekli)
        if ($isDryRun) {
            $this->info("🎯 DRY RUN: {$targetSymbol} pozisyonu açılacaktı");
            $this->line("📋 Action: {$targetAction}");
            $this->line("📋 Confidence: {$targetResult['confidence']}%");
            $this->line("📋 Leverage: {$targetResult['leverage']}x");
        } else {
            $this->info("🚀 {$targetSymbol} pozisyonu açılıyor...");
            // TODO: Gerçek pozisyon açma kodu
            $this->warn('⚠️ Gerçek pozisyon açma henüz implement edilmedi');
        }

        $this->line('');
        $this->info('✅ İşlem tamamlandı!');

        return self::SUCCESS;
    }
}
