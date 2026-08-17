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
        Schema::create('traffic_fine_lookup_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plate', 16);
            $table->string('vehicle_type', 32);
            $table->string('source', 32);
            $table->boolean('cache_hit')->default(false);
            $table->string('provider', 64)->nullable();
            $table->unsignedInteger('provider_latency_ms')->nullable();
            $table->string('status', 32);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'status']);
            $table->index(['plate', 'vehicle_type', 'created_at'], 'traffic_fine_logs_lookup_index');
            $table->index(['provider', 'created_at']);
            $table->index(['cache_hit', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_fine_lookup_logs');
    }
};
