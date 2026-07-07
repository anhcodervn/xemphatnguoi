<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captcha_services', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('default_source_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('captcha_services', function (Blueprint $table): void {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
