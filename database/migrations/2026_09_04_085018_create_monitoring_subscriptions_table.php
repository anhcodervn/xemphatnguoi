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
        Schema::create('monitoring_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_id')->constrained()->restrictOnDelete();
            $table->string('plan_name');
            $table->unsignedInteger('vehicle_limit');
            $table->decimal('unit_price', 20, 2);
            $table->decimal('total_price', 20, 2);
            $table->unsignedInteger('duration_days');
            $table->string('status')->default('active')->index();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_subscriptions');
    }
};
