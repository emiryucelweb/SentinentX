<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_runs', function (Blueprint $table) {
            $table->id();
            $table->json('symbols'); // Çoklu sembol desteği
            $table->decimal('initial_equity', 15, 2);
            $table->decimal('final_equity', 15, 2)->nullable();
            $table->decimal('risk_pct', 5, 2);
            $table->integer('max_leverage');
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('final_pf', 10, 6)->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->enum('status', ['RUNNING', 'COMPLETED', 'FAILED'])->default('RUNNING');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_runs');
    }
};
