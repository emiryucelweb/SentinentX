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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role')->default('user')->index();
            $table->json('meta')->nullable();

            $table->index(['tenant_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite uyumluluğu için down metodunu basitleştir
        if (config('database.default') !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'role']);
                $table->dropForeign(['tenant_id']);
                $table->dropColumn(['tenant_id', 'role', 'meta']);
            });
        }
    }
};
