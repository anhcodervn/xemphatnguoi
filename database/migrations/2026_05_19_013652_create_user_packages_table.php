<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('expired_at');
            $table->unsignedBigInteger('request_used')->default(0);
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['package_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_packages');
    }
};
