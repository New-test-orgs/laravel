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
        Schema::create('store_migrations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('source');
            $table->string('target');
            $table->string('status');
            $table->string('repository');
            $table->unsignedInteger('executions')->default(0);
            $table->string('last_script')->nullable();
            $table->string('owner');
            $table->string('initials', 8);
            $table->string('avatar', 32);
            $table->timestamp('started_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_migrations');
    }
};
