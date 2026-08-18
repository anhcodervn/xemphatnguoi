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
        Schema::create('seo_post_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_post_id')->constrained('seo_posts')->cascadeOnDelete();
            $table->string('actor_type')->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['seo_post_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_post_activity_logs');
    }
};
