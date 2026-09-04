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
        Schema::create('monitoring_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->unsignedInteger('vehicle_limit')->nullable();
            $table->decimal('price', 20, 2)->nullable();
            $table->decimal('unit_price', 20, 2)->nullable();
            $table->unsignedInteger('min_vehicle_count')->default(20);
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_plans');
    }
};
