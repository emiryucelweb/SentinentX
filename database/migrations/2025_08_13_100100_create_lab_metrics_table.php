<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_metrics', function (Blueprint $t) {
            $t->id();
            $t->date('as_of');
            $t->decimal('pf', 10, 4)->nullable();
            $t->decimal('maxdd_pct', 10, 4)->nullable();
            $t->decimal('sharpe', 10, 4)->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(['as_of']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_metrics');
    }
};
