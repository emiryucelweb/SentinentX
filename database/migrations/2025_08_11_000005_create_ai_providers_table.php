<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();            // openai, gemini, grok
            $t->boolean('enabled')->default(true);
            $t->string('model')->nullable();
            $t->unsignedInteger('timeout_ms')->default(30000);
            $t->unsignedInteger('max_tokens')->default(2048);
            $t->unsignedTinyInteger('priority')->default(10);
            $t->decimal('weight', 3, 2)->default(1.00);  // <— eklendi
            $t->decimal('cost_per_1k_tokens', 8, 4)->default(0);
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
