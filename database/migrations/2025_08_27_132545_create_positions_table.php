<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('trade_id')->nullable()->constrained()->onDelete('set null');
            $table->string('symbol');
            $table->enum('side', ['Long', 'Short']);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('qty', 20, 8);
            $table->integer('leverage')->default(1);
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->enum('status', ['OPEN', 'CLOSED', 'CANCELLED'])->default('OPEN');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['symbol', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('symbol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
