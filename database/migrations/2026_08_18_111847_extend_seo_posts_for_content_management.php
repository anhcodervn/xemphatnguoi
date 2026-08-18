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
        Schema::table('seo_posts', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('status');
            $table->text('source_url')->nullable()->after('source_type');
            $table->char('source_url_hash', 64)->nullable()->unique()->after('source_url');
            $table->string('source_title')->nullable()->after('source_url_hash');
            $table->string('source_domain')->nullable()->after('source_title')->index();
            $table->char('content_hash', 64)->nullable()->unique()->after('source_domain');
            $table->string('external_id')->nullable()->unique()->after('content_hash');
            $table->string('created_by_type')->default('admin')->after('external_id')->index();
            $table->foreignId('created_by_id')->nullable()->after('created_by_type')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('published_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('published_by');
            $table->string('index_status')->default('index')->after('robots')->index();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['index_status']);
            $table->dropConstrainedForeignId('published_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropIndex(['created_by_type']);
            $table->dropIndex(['source_domain']);
            $table->dropUnique(['external_id']);
            $table->dropUnique(['content_hash']);
            $table->dropUnique(['source_url_hash']);
            $table->dropColumn([
                'source_type',
                'source_url',
                'source_url_hash',
                'source_title',
                'source_domain',
                'content_hash',
                'external_id',
                'created_by_type',
                'reviewed_at',
                'rejection_reason',
                'index_status',
            ]);
        });
    }
};
