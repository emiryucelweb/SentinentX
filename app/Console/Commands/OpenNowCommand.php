<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\ConsensusService;
use Illuminate\Console\Command;

final class OpenNowCommand extends Command
{
    protected $signature = 'sentx:open-now 
        {symbol? : Backward-compat single symbol (e.g., BTCUSDT)}
        {--symbols= : Trading symbols (e.g., BTC,ETH,SOL,XRP or BTCUSDT,ETHUSDT,SOLUSDT,XRPUSDT)} 
        {--snapshot= : Path to snapshot JSON file}
        {--dry : Dry run mode - no actual trades}';

    protected $description = 'Open new position using AI consensus (2-round voting)';

    public function handle(ConsensusService $consensus): int
    {
        $symbolsOpt = (string) ($this->option('symbols') ?? '');
        $argSymbol = (string) ($this->argument('symbol') ?? '');
        $symbols = $symbolsOpt !== '' ? $symbolsOpt : ($argSymbol !== '' ? $argSymbol : 'BTC,ETH,SOL,XRP');
        $symbolList = array_map('trim', explode(',', $symbols));
        $isDryRun = (bool) $this->option('dry');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No actual trades will be executed');
        }

        $path = (string) $this->option('snapshot');
        if (! $path || ! is_file($path)) {
            $this->error('--snapshot=/path/to/snapshot.json zorunlu (AŞAMA 1)');

            return self::FAILURE;
        }

        $snap = json_decode((string) file_get_contents($path), true);
        if (! is_array($snap)) {
            $this->error('Snapshot JSON okunamadı');

            return self::FAILURE;
        }

        // Snapshot schema validation
        if (! isset($snap['timestamp'], $snap['symbols'], $snap['market_data'])) {
            $this->error('Snapshot schema validation failed: missing required fields');

            return self::FAILURE;
        }

        $snap['symbols'] = $symbolList;
        $snap['dry_run'] = $isDryRun;

        // trading.php konfiglerini ekle
        $snap['mode'] = config('trading.mode');
        $snap['risk'] = config('trading.risk');
        $snap['execution'] = config('trading.execution');

        if ($isDryRun) {
            $this->info('📊 Running consensus in dry run mode...');
            $this->info('Symbols: '.implode(', ', $symbolList));
            $this->info('Snapshot: '.$path);
        }

        try {
            $result = $consensus->decide($snap);

            if ($isDryRun) {
                $this->info('✅ DRY RUN COMPLETED');
                $this->info('Decision: '.($result['action'] ?? 'UNKNOWN'));
                $this->info('Confidence: '.($result['confidence'] ?? 0));
            }
        } catch (\Exception $e) {
            $this->error('Consensus failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /**
     * Snapshot JSON schema validation
     */
    /**
     * @param  array<string, mixed>  $snap
     * @return array<string, mixed>
     */
    private function validateSnapshotSchema(array $snap): array
    {
        $requiredFields = ['market_data', 'portfolio', 'risk_context'];

        foreach ($requiredFields as $field) {
            if (! isset($snap[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        // Market data validation
        if (! is_array($snap['market_data']) || empty($snap['market_data'])) {
            return [
                'valid' => false,
                'error' => 'market_data must be a non-empty array',
            ];
        }

        // Portfolio validation
        if (! is_array($snap['portfolio']) || ! isset($snap['portfolio']['equity'])) {
            return [
                'valid' => false,
                'error' => 'portfolio must contain equity field',
            ];
        }

        // Risk context validation
        if (! is_array($snap['risk_context']) || ! isset($snap['risk_context']['im_buffer_factor'])) {
            return [
                'valid' => false,
                'error' => 'risk_context must contain im_buffer_factor field',
            ];
        }

        return ['valid' => true, 'error' => null];
    }
}
