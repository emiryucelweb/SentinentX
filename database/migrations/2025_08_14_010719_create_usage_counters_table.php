<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('service')->index();
            $table->unsignedInteger('count')->default(0);
            $table->string('period')->index(); // daily, monthly, yearly
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'service', 'period']);
            $table->index(['service', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
