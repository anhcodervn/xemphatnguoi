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
        Schema::create('proxy_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('proxy_check_batch_id')
                ->constrained('proxy_check_batches')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('endpoint', 64);
            $table->text('proxy')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('exit_ip', 45)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('message', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['proxy_check_batch_id', 'position']);
            $table->index(['proxy_check_batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxy_check_items');
    }
};
