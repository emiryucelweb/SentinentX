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
        // AI karar logları
        Schema::create('ai_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->index();
            $table->string('symbol', 20)->index();
            $table->enum('decision_type', ['position_open', 'position_manage', 'position_close', 'scan']);
            $table->string('ai_provider', 50);
            $table->string('decision', 20)->nullable(); // LONG, SHORT, NONE, HOLD, CLOSE
            $table->tinyInteger('confidence')->nullable(); // 0-100
            $table->decimal('leverage', 8, 2)->nullable();
            $table->decimal('stop_loss', 16, 8)->nullable();
            $table->decimal('take_profit', 16, 8)->nullable();
            $table->text('reason')->nullable();
            $table->decimal('market_price', 16, 8)->nullable();
            $table->decimal('coingecko_score', 5, 2)->nullable();
            $table->decimal('market_sentiment', 5, 2)->nullable();
            $table->string('risk_profile', 20)->default('moderate');
            $table->json('context_data')->nullable();
            $table->json('ai_response')->nullable();
            $table->timestamps();

            // İndeksler
            $table->index(['user_id', 'created_at']);
            $table->index(['symbol', 'created_at']);
            $table->index(['decision_type', 'created_at']);
            $table->index(['ai_provider', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Pozisyon logları
        Schema::create('position_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->index();
            $table->string('symbol', 20)->index();
            $table->enum('action', ['OPEN', 'CLOSE', 'UPDATE'])->index();
            $table->enum('side', ['LONG', 'SHORT'])->nullable();
            
            // Fiyat bilgileri
            $table->decimal('entry_price', 16, 8)->nullable();
            $table->decimal('exit_price', 16, 8)->nullable();
            $table->decimal('qty', 20, 8)->nullable();
            $table->decimal('leverage', 8, 2)->nullable();
            
            // SL/TP bilgileri
            $table->decimal('stop_loss', 16, 8)->nullable();
            $table->decimal('take_profit', 16, 8)->nullable();
            $table->decimal('old_stop_loss', 16, 8)->nullable();
            $table->decimal('new_stop_loss', 16, 8)->nullable();
            $table->decimal('old_take_profit', 16, 8)->nullable();
            $table->decimal('new_take_profit', 16, 8)->nullable();
            
            // PnL bilgileri
            $table->decimal('pnl', 16, 8)->nullable();
            $table->decimal('pnl_percentage', 10, 4)->nullable();
            
            // AI ve execution bilgileri
            $table->tinyInteger('ai_confidence')->nullable();
            $table->text('ai_reason')->nullable();
            $table->decimal('execution_price', 16, 8)->nullable();
            $table->decimal('execution_fee', 16, 8)->default(0);
            $table->decimal('total_fees', 16, 8)->default(0);
            $table->decimal('slippage', 8, 4)->nullable(); // %
            
            // Kapanış/güncelleme bilgileri
            $table->string('close_reason', 100)->nullable();
            $table->string('update_reason', 100)->nullable();
            $table->integer('duration_minutes')->nullable();
            
            // External referanslar
            $table->string('bybit_order_id', 100)->nullable();
            $table->string('risk_profile', 20)->default('moderate');
            
            // Market koşulları
            $table->json('market_conditions')->nullable();
            $table->decimal('market_price', 16, 8)->nullable();
            
            $table->timestamps();

            // İndeksler
            $table->index(['user_id', 'created_at']);
            $table->index(['trade_id', 'action']);
            $table->index(['symbol', 'action', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['side', 'created_at']);
            $table->index(['close_reason', 'created_at']);
            
            // PnL analizleri için
            $table->index(['user_id', 'action', 'pnl']);
            $table->index(['symbol', 'action', 'pnl']);
        });

        // Performans özet tablosu (günlük/haftalık/aylık özetler için)
        Schema::create('performance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->index();
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->index();
            $table->date('period_date'); // Gün/hafta/ay başlangıcı
            $table->string('risk_profile', 20)->default('moderate');
            
            // Trading istatistikleri
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0); // %
            $table->decimal('total_pnl', 16, 8)->default(0);
            $table->decimal('total_fees', 16, 8)->default(0);
            $table->decimal('net_pnl', 16, 8)->default(0);
            $table->decimal('profit_factor', 8, 4)->default(0);
            $table->decimal('max_drawdown', 5, 2)->default(0); // %
            $table->decimal('avg_trade_duration', 8, 2)->default(0); // dakika
            
            // AI istatistikleri
            $table->integer('ai_decisions')->default(0);
            $table->decimal('avg_ai_confidence', 5, 2)->default(0);
            $table->integer('high_confidence_decisions')->default(0); // >70
            $table->integer('low_confidence_decisions')->default(0); // <=70
            
            // Sembol dağılımı
            $table->json('symbol_stats')->nullable();
            $table->json('ai_provider_stats')->nullable();
            
            $table->timestamps();

            // İndeksler
            $table->unique(['user_id', 'period_type', 'period_date']);
            $table->index(['tenant_id', 'period_type', 'period_date']);
            $table->index(['period_type', 'period_date', 'win_rate']);
            $table->index(['period_type', 'period_date', 'profit_factor']);
            $table->index(['risk_profile', 'period_type', 'net_pnl']);
        });

        // Backtest için historik veriler
        Schema::create('backtest_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->index();
            $table->string('run_id', 50)->index(); // Her backtest çalışması için unique ID
            $table->string('symbol', 20);
            $table->timestamp('trade_time');
            
            // Market verileri
            $table->decimal('open_price', 16, 8);
            $table->decimal('high_price', 16, 8);
            $table->decimal('low_price', 16, 8);
            $table->decimal('close_price', 16, 8);
            $table->decimal('volume', 20, 8);
            
            // AI kararları
            $table->json('ai_decisions');
            $table->string('final_decision', 20); // LONG, SHORT, NONE
            $table->tinyInteger('consensus_confidence');
            
            // Simülasyon sonuçları
            $table->enum('position_side', ['LONG', 'SHORT'])->nullable();
            $table->decimal('entry_price', 16, 8)->nullable();
            $table->decimal('exit_price', 16, 8)->nullable();
            $table->decimal('pnl', 16, 8)->nullable();
            $table->boolean('is_winner')->nullable();
            
            // CoinGecko verileri
            $table->decimal('coingecko_reliability', 5, 2)->nullable();
            $table->decimal('coingecko_sentiment', 5, 2)->nullable();
            
            $table->timestamps();

            // İndeksler
            $table->index(['user_id', 'run_id']);
            $table->index(['symbol', 'trade_time']);
            $table->index(['run_id', 'trade_time']);
            $table->index(['tenant_id', 'run_id']);
            $table->index(['final_decision', 'trade_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_data');
        Schema::dropIfExists('performance_summaries');
        Schema::dropIfExists('position_logs');
        Schema::dropIfExists('ai_decision_logs');
    }
};
