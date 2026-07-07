<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('api_logs', 'service_response_data')) {
                $table->json('service_response_data')->nullable()->after('request_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('api_logs', 'service_response_data')) {
                $table->dropColumn('service_response_data');
            }
        });
    }
};
