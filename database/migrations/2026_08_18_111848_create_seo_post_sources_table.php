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
        Schema::create('seo_post_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_post_id')->constrained('seo_posts')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('url');
            $table->char('url_hash', 64);
            $table->string('domain')->nullable()->index();
            $table->string('type')->nullable()->index();
            $table->timestamps();

            $table->unique(['seo_post_id', 'url_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_post_sources');
    }
};
