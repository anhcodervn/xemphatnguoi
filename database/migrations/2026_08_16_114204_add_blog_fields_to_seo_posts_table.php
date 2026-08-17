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
        Schema::table('seo_posts', function (Blueprint $table): void {
            $table->text('thumbnail')->nullable()->after('excerpt');
            $table->json('tags')->nullable()->after('focus_keyword');
            $table->text('og_image')->nullable()->after('canonical_url');
            $table->json('faq')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_posts', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail', 'tags', 'og_image', 'faq']);
        });
    }
};
