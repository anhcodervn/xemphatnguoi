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
        Schema::table('seo_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('seo_categories')->nullOnDelete();
            $table->text('description')->nullable()->after('slug');
            $table->string('created_by_type')->default('admin')->after('sort_order')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropIndex(['created_by_type']);
            $table->dropColumn(['description', 'created_by_type']);
        });
    }
};
