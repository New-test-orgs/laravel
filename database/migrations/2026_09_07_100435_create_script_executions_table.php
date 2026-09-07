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
        Schema::create('script_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_migration_id')->constrained('store_migrations')->cascadeOnDelete();
            $table->string('script');
            $table->string('status');
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('total');
            $table->string('commit')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('requested_by');
            $table->string('initials', 8);
            $table->string('avatar', 32);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_executions');
    }
};
