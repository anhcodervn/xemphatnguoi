<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_post_tag', function (Blueprint $table): void {
            $table->foreignId('seo_post_id')->constrained('seo_posts')->cascadeOnDelete();
            $table->foreignId('seo_tag_id')->constrained('seo_tags')->cascadeOnDelete();
            $table->primary(['seo_post_id', 'seo_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_post_tag');
    }
};
