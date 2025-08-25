<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\Trading\PositionManager;
use Illuminate\Console\Command;

final class ManagePositions extends Command
{
    protected $signature = 'sentx:manage-positions {--snapshot=}';

    protected $description = 'Açık pozisyonlar için AI tabanlı yönetim (scale-in/out, CLOSE, SL/TP).';

    public function handle(PositionManager $pm): int
    {
        $path = (string) $this->option('snapshot');
        if (! $path || ! is_file($path)) {
            $this->error('--snapshot=/path/to/manage_snapshot.json zorunlu');

            return self::FAILURE;
        }
        $snap = json_decode((string) file_get_contents($path), true);
        if (! is_array($snap)) {
            $this->error('Snapshot JSON okunamadı');

            return self::FAILURE;
        }

        $open = Trade::query()->where('status', 'OPEN')->get();
        $out = [];
        foreach ($open as $t) {
            try {
                $res = $pm->manage($t, $snap);
                $out[$t->id] = $res;
            } catch (\Exception $e) {
                $out[$t->id] = [
                    'action' => 'ERROR',
                    'error' => $e->getMessage(),
                ];
            }
        }
        $this->line(json_encode(['managed' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
