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
        Schema::create('traffic_fine_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('label', 120);
            $table->string('driver', 64)->index();
            $table->text('api_url');
            $table->longText('api_token');
            $table->unsignedTinyInteger('timeout')->default(10);
            $table->unsignedTinyInteger('connect_timeout')->default(3);
            $table->unsignedTinyInteger('retry_times')->default(2);
            $table->unsignedSmallInteger('retry_sleep_ms')->default(200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_fine_providers');
    }
};
