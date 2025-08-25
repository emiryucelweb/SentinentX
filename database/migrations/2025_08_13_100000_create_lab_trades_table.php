<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_trades', function (Blueprint $t) {
            $t->id();
            $t->string('symbol');
            $t->enum('side', ['LONG', 'SHORT']);
            $t->decimal('qty', 24, 10)->default(0);
            $t->decimal('entry_price', 24, 10);
            $t->decimal('exit_price', 24, 10)->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->decimal('pnl_quote', 24, 10)->nullable();
            $t->decimal('pnl_pct', 12, 6)->nullable();
            $t->string('cycle_uuid')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();

            $t->index(['symbol', 'opened_at']);
            $t->index(['closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_trades');
    }
};
