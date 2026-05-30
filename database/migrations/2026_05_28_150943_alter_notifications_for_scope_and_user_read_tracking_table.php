<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'scope')) {
                $table->string('scope', 30)->default('user')->after('user_id');
            }
        });

        DB::statement('ALTER TABLE notifications DROP FOREIGN KEY notifications_user_id_foreign');
        DB::statement('ALTER TABLE notifications MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['scope', 'created_at'], 'notifications_scope_created_at_index');
            $table->index(['scope', 'user_id'], 'notifications_scope_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_scope_created_at_index');
            $table->dropIndex('notifications_scope_user_id_index');
            $table->dropColumn('scope');
        });

        DB::statement('ALTER TABLE notifications DROP FOREIGN KEY notifications_user_id_foreign');
        DB::statement('ALTER TABLE notifications MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
};
