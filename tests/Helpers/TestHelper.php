<?php

namespace Tests\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TestHelper
{
    /**
     * Test için deterministik tarih ayarla
     */
    public static function setDeterministicDate(string $date = '2025-08-14 12:00:00'): void
    {
        Carbon::setTestNow($date);
    }

    /**
     * Test için deterministik seed ayarla
     */
    public static function setDeterministicSeed(int $seed = 42): void
    {
        mt_srand($seed);
        srand($seed);
    }

    /**
     * Test sonrası temizlik
     */
    public static function cleanup(): void
    {
        Carbon::setTestNow();
        mt_srand();
        srand();
    }

    /**
     * LAB testleri için deterministik veri oluştur
     */
    public static function createDeterministicLabData(string $symbol = 'BTCUSDT', int $count = 5): void
    {
        // Deterministik fiyat hareketleri için seed kullan
        self::setDeterministicSeed(42);

        // Test verisi oluştur (LAB scan komutları için)
        // Bu method LAB testlerinde kullanılabilir
    }

    /**
     * Test database'ini temizle
     */
    public static function cleanTestDatabase(): void
    {
        // Sadece test environment'da çalıştır
        if (app()->environment('testing')) {
            DB::statement('PRAGMA foreign_keys = OFF');
            $tables = DB::select('SELECT name FROM sqlite_master WHERE type="table"');
            foreach ($tables as $table) {
                if ($table->name !== 'sqlite_sequence') {
                    DB::statement('DELETE FROM '.$table->name);
                }
            }
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
