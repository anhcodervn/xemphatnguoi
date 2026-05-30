<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table): void {
            $table->unsignedInteger('limit_request_per_minute')
                ->default(6)
                ->after('sort_order')
                ->comment('Maximum sync requests per minute for this bank provider.');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table): void {
            $table->dropColumn('limit_request_per_minute');
        });
    }
};
