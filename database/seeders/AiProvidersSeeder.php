<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AiProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'gpt', 'priority' => 1, 'timeout_ms' => 60000, 'cost_per_1k_tokens' => '0.0', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'gemini', 'priority' => 2, 'timeout_ms' => 60000, 'cost_per_1k_tokens' => '0.0', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'grok', 'priority' => 3, 'timeout_ms' => 60000, 'cost_per_1k_tokens' => '0.0', 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach ($rows as $r) {
            DB::table('ai_providers')->updateOrInsert(['name' => $r['name']], $r);
        }
    }
}
