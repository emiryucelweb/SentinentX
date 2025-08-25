<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip - alerts table already created in comprehensive testing tables
        if (Schema::hasTable('alerts')) {
            return;
        }

        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();
            $t->text('message');
            $t->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info')->index();
            $t->json('context')->nullable();
            $t->string('status')->default('active')->index();
            $t->unsignedBigInteger('acknowledged_by')->nullable();
            $t->timestamp('acknowledged_at')->nullable();
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution')->nullable();
            $t->timestamps();

            $t->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $t->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
