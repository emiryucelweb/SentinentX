<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['active', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
