<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_recharge', function (Blueprint $table): void {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->text('qr_template');
            $table->string('transfer_prefix', 50);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('transfer_prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_recharge');
    }
};
