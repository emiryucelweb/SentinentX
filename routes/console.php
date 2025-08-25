<?php

use App\Support\BybitHelpers;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
| CLI (Artisan) komutlarını burada tanımlıyoruz.
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * BYBIT: env anahtarlarını hızlı doğrula (verbose)
 */
Artisan::command('bybit:check-key-verbose', function () {
    $apiKey = (string) config('exchange.bybit.api_key', '');
    $secret = (string) config('exchange.bybit.api_secret', '');
    $base = rtrim((string) config('exchange.bybit.base_url', 'https://api.bybit.com'), '/');

    if (! $apiKey || ! $secret) {
        $this->error('BYBIT_API_KEY/SECRET boş.');

        return 1;
    }
    if ($base === '') {
        $this->warn('BYBIT_BASE_URL boş. Otomatik tarama için: php artisan bybit:probe-env');
        $base = 'https://api.bybit.com';
    }

    $ts = (string) BybitHelpers::serverTime($base);
    $rw = (string) config('exchange.bybit.recv_window', 15000);
    $sign = BybitHelpers::signV5($ts, $apiKey, $rw, $secret);

    $this->info('--- DEBUG ---');
    $this->line('base       : '.$base);
    $this->line('timestamp  : '.$ts);
    $this->line('api_key    : '.$apiKey);
    $this->line('recv_window: '.$rw);
    $this->line('presign    : '.$ts.$apiKey.$rw);
    $this->line('signType   : 2');
    $this->line('sign       : '.$sign);

    $out = BybitHelpers::queryApi($base, $apiKey, $secret, (int) $rw);
    $this->line('HTTP '.$out['http'].' '.json_encode($out['json']));
    $rc = $out['json']['retCode'] ?? null;

    if ($rc === 0) {
        $this->info('✅ retCode=0 → Anahtar geçerli ve domain doğru.');

        return 0;
    }
    if ($rc === 10003) {
        $this->error('⚠ 10003: API key invalid. Büyük olasılıkla yanlış domain/ortam.');

        return 2;
    }
    if ($rc === 10002) {
        $this->error('⚠ 10002: Zaman / recv_window kontrol et.');

        return 3;
    }
    $this->warn('⚠ retCode='.$rc.' → Ayrıntı için mesajı kontrol et.');

    return 4;
})->purpose('Bybit API key doğrulama (verbose)');

/**
 * BYBIT: Otomatik ortam taraması (TR, Demo, Mainnet, Testnet)
 */
Artisan::command('bybit:probe-env', function () {
    $apiKey = env('BYBIT_API_KEY');
    $secret = env('BYBIT_API_SECRET');

    if (! $apiKey || ! $secret) {
        $this->error('BYBIT_API_KEY/SECRET boş.');

        return 1;
    }

    $candidates = [];
    if ($envBase = env('BYBIT_BASE_URL')) {
        $candidates[] = rtrim($envBase, '/');
    }
    // En olasılar (sırayla):
    foreach ([
        'https://api.bybit-tr.com',       // TR mainnet
        'https://api-demo.bybit.com',     // Demo Trading
        'https://api.bybit.com',          // Global mainnet
        'https://api-testnet.bybit.com',  // Testnet
        'https://api.bytick.com',         // Alternatif global
    ] as $d) {
        if (! in_array($d, $candidates, true)) {
            $candidates[] = $d;
        }
    }

    $okBase = null;
    foreach ($candidates as $base) {
        $this->line("==> Deneniyor: {$base}");
        $out = BybitHelpers::queryApi($base, $apiKey, $secret, (int) (env('BYBIT_RECV_WINDOW', 15000)));
        $this->line('  HTTP '.$out['http'].' '.json_encode($out['json']));

        $rc = $out['json']['retCode'] ?? null;
        if ($rc === 0) {
            $this->info("  ✅ OK (retCode=0): {$base}");
            $okBase = $base;
            break;
        } elseif ($rc === 10003) {
            $this->warn("  ⚠ 10003: API key invalid ({$base}).");
        } elseif ($rc === 10002) {
            $this->warn("  ⚠ 10002: Zaman/recv_window ({$base}).");
        } else {
            $this->warn("  ⚠ retCode={$rc} ({$base}).");
        }
    }

    if ($okBase) {
        $this->info("Sonuç: Doğru domain bu görünüyor -> {$okBase}");
        $this->line("Lütfen .env dosyanda BYBIT_BASE_URL='{$okBase}' olarak ayarla ve:");
        $this->line('  php artisan config:clear && php artisan bybit:check-key-verbose');

        return 0;
    }

    $this->error('Sonuç: Hiçbir domain retCode=0 vermedi.');

    return 2;
})->purpose('Bybit domain/ortam otomatik tespiti');
