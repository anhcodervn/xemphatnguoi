<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('driver')->default('manual');
            $table->string('api_base_url')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_sources');
    }
};
