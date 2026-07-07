<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captcha_sources', function (Blueprint $table) {
            $table->decimal('balance', 18, 4)->nullable()->after('api_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('captcha_sources', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
