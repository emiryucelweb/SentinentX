<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consensus_decisions', function (Blueprint $t) {
            $t->id();
            $t->uuid('cycle_uuid')->unique();
            $t->string('symbol')->index();
            $t->json('round1')->nullable();
            $t->json('round2')->nullable();
            $t->enum('final_action', ['LONG', 'SHORT', 'HOLD', 'CLOSE']);
            $t->unsignedTinyInteger('final_confidence');
            $t->json('meta')->nullable();
            $t->boolean('majority_lock')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consensus_decisions');
    }
};
