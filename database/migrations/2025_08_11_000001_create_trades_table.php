<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $t) {
            $t->id();
            $t->string('symbol');
            $t->enum('side', ['LONG', 'SHORT']);
            $t->enum('status', ['OPEN', 'CLOSED', 'CANCELLED'])->index();
            $t->enum('margin_mode', ['CROSS', 'ISOLATED'])->default('CROSS');
            $t->unsignedSmallInteger('leverage')->default(1);
            $t->decimal('qty', 18, 8);
            $t->decimal('entry_price', 18, 8);
            $t->decimal('take_profit', 18, 8)->nullable();
            $t->decimal('stop_loss', 18, 8)->nullable();
            $t->decimal('pnl', 18, 8)->nullable();              // <— eklendi
            $t->decimal('pnl_realized', 18, 8)->nullable();
            $t->decimal('fees_total', 18, 8)->default(0);
            $t->string('bybit_order_id')->nullable()->index();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['symbol', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
