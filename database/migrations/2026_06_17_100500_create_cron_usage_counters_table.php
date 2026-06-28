<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_usage_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('month', 7)->index();
            $table->unsignedBigInteger('total_runs')->default(0);
            $table->unsignedBigInteger('successful_runs')->default(0);
            $table->unsignedBigInteger('failed_runs')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_usage_counters');
    }
};
