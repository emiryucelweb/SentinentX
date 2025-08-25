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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('action')->index(); // e.g., 'trade.created', 'auth.login'
            $table->string('resource_type')->index(); // e.g., 'Trade', 'User'
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->string('request_id')->nullable()->index();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
