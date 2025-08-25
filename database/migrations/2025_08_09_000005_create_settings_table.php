<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('param_name', 128)->index();
            $table->text('param_value')->nullable();
            $table->timestampsTz(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
