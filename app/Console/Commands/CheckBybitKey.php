<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckBybitKey extends Command
{
    protected $signature = 'bybit:check-key-env';

    protected $description = 'Bybit API key, secret ve base URL değerlerini .env ve config üzerinden kontrol eder.';

    public function handle(): int
    {
        // Config üzerinden okuma (cache-safe)
        $envKey = config('services.bybit.key');
        $envSecret = config('services.bybit.secret');
        $envBaseUrl = config('services.bybit.base_url');

        // Config/services.php üzerinden okuma
        $confKey = config('services.bybit.key');
        $confSecret = config('services.bybit.secret');
        $confBaseUrl = config('services.bybit.base_url');

        $mask = fn ($v) => $v ? Str::mask($v, '*', 0, max(strlen($v) - 4, 0)) : '(null)';

        $this->info('--- ENV ---');
        $this->line('BYBIT_API_KEY:     '.$mask($envKey));
        $this->line('BYBIT_API_SECRET:  '.$mask($envSecret));
        $this->line('BYBIT_BASE_URL:    '.($envBaseUrl ?: '(null)'));

        $this->info('--- CONFIG ---');
        $this->line('services.bybit.key:       '.$mask($confKey));
        $this->line('services.bybit.secret:    '.$mask($confSecret));
        $this->line('services.bybit.base_url:  '.($confBaseUrl ?: '(null)'));

        return 0;
    }
}
