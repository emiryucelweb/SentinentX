<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestampTz('timestamp', 0)->index();
            $table->string('symbol', 32)->index();
            $table->decimal('open', 20, 8)->nullable();
            $table->decimal('high', 20, 8)->nullable();
            $table->decimal('low', 20, 8)->nullable();
            $table->decimal('close', 20, 8)->nullable();
            $table->decimal('volume', 32, 8)->nullable();
            $table->json('indicators')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
