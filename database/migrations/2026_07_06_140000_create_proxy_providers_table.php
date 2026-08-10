<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('driver')->default('generic_rest');
            $table->string('api_base_url')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_providers');
    }
};
