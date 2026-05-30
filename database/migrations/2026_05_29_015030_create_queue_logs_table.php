<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('job_uuid', 100)->nullable()->index();
            $table->string('connection', 50)->nullable()->index();
            $table->string('queue', 100)->nullable()->index();
            $table->string('job_name', 255)->nullable()->index();
            $table->string('status', 30)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processing_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['queue', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_logs');
    }
};
