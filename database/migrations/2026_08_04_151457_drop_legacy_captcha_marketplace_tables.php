<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('captcha_tasks');
        Schema::dropIfExists('captcha_services');
        Schema::dropIfExists('captcha_sources');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy marketplace data is intentionally not recreated after removal.
    }
};
