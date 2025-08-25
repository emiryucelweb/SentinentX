<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add SaaS plan column to tenants table
     */
    public function up(): void
    {
        // Skip in testing environment - tables are created manually in tests
        if (app()->environment('testing')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'plan')) {
                $table->enum('plan', ['starter', 'professional', 'institutional', 'enterprise'])
                    ->default('starter')
                    ->after('name');
            }

            if (! Schema::hasColumn('tenants', 'billing_email')) {
                $table->string('billing_email')->nullable()->after('plan');
            }

            if (! Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('billing_email');
            }
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Check if columns exist before dropping (SQLite compatibility)
            if (Schema::hasColumn('tenants', 'plan')) {
                $table->dropColumn('plan');
            }
            if (Schema::hasColumn('tenants', 'billing_email')) {
                $table->dropColumn('billing_email');
            }
            if (Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->dropColumn('subscription_ends_at');
            }
        });
    }
};
