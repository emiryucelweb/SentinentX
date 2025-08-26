<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RiskProfileCommand extends Command
{
    protected $signature = 'sentx:risk-profile {symbol}';

    protected $description = 'Risk profili seçimi ve kaldıraç belirleme';

    public function handle(): int
    {
        $symbol = strtoupper((string) $this->argument('symbol'));

        $this->info("🎯 {$symbol} için Risk Profili Seçimi");
        $this->line('');

        $riskProfiles = [
            1 => [
                'name' => 'Düşük Risk',
                'leverage_range' => '3-15x',
                'min_leverage' => 3,
                'max_leverage' => 15,
                'position_size_pct' => 5,
                'description' => 'Güvenli, düşük volatilite',
            ],
            2 => [
                'name' => 'Orta Risk',
                'leverage_range' => '15-45x',
                'min_leverage' => 15,
                'max_leverage' => 45,
                'position_size_pct' => 10,
                'description' => 'Standart risk/getiri oranı',
            ],
            3 => [
                'name' => 'Yüksek Risk',
                'leverage_range' => '45-125x',
                'min_leverage' => 45,
                'max_leverage' => 125,
                'position_size_pct' => 15,
                'description' => 'Yüksek getiri potansiyeli, yüksek risk',
            ],
        ];

        foreach ($riskProfiles as $id => $profile) {
            $this->line("📊 {$id}. {$profile['name']}");
            $this->line("   Kaldıraç: {$profile['leverage_range']}");
            $this->line("   Pozisyon: Bakiye'nin %{$profile['position_size_pct']}'i");
            $this->line("   {$profile['description']}");
            $this->line('');
        }

        $choice = $this->choice(
            'Risk profilini seçin',
            array_map(fn ($p) => $p['name'], $riskProfiles),
            $riskProfiles[2]['name'] // Default: Dengeli
        );

        // Seçilen profili bul
        $selectedProfile = null;
        foreach ($riskProfiles as $profile) {
            if ($profile['name'] === $choice) {
                $selectedProfile = $profile;
                break;
            }
        }

        if (! $selectedProfile) {
            $this->error('Geçersiz seçim');

            return self::FAILURE;
        }

        $this->info('✅ Risk Profili Seçildi:');
        $this->line("📊 Profil: {$selectedProfile['name']}");
        $this->line("⚡ Kaldıraç Aralığı: {$selectedProfile['leverage_range']}");
        $this->line("💰 Max Pozisyon: Bakiye'nin %{$selectedProfile['position_size_pct']}'i");
        $this->line("🤖 AI'lar bu aralıkta kaldıraç seçecek ve ortalaması alınacak");

        // Risk bilgilerini snapshot'a ekle
        $riskContext = [
            'risk_profile' => $selectedProfile['name'],
            'min_leverage' => $selectedProfile['min_leverage'],
            'max_leverage' => $selectedProfile['max_leverage'],
            'position_size_pct' => $selectedProfile['position_size_pct'],
            'risk_level' => array_search($selectedProfile, $riskProfiles),
        ];

        $this->line('');
        $this->line('Risk Context for AI:');
        $this->line(json_encode($riskContext, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
