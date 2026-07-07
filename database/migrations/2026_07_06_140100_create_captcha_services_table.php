<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_services', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('image');
            $table->text('description')->nullable();
            $table->string('provider_service_code')->nullable();
            $table->foreignId('default_source_id')->nullable()->constrained('captcha_sources')->nullOnDelete();
            $table->decimal('base_price', 12, 4)->default(0);
            $table->decimal('selling_price', 12, 4)->default(0);
            $table->unsignedInteger('estimated_seconds')->default(15);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_services');
    }
};
