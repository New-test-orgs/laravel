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
        Schema::create('script_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('script_execution_id')->constrained('script_executions')->cascadeOnDelete();
            $table->foreignId('store_migration_id')->constrained('store_migrations')->cascadeOnDelete();
            $table->string('level');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_execution_logs');
    }
};
