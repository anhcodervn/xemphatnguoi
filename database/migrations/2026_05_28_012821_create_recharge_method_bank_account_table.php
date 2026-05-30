<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_method_bank_account', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recharge_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['recharge_method_id', 'bank_account_id'], 'recharge_method_bank_account_unique');
            $table->index(['recharge_method_id', 'is_active', 'sort_order'], 'recharge_method_bank_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_method_bank_account');
    }
};
