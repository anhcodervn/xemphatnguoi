<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 20, 2);
            $table->unsignedInteger('duration_days');
            $table->unsignedBigInteger('request_limit')->default(0);
            $table->unsignedInteger('request_per_minute')->default(60);
            $table->unsignedInteger('concurrent_limit')->default(1);
            $table->json('features')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->comment('Sellable API rental packages.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
