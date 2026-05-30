<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('hold_balance', 20, 2)->default(0);
            $table->decimal('total_recharge', 20, 2)->default(0);
            $table->decimal('total_spent', 20, 2)->default(0);
            $table->timestamps();

            $table->index('created_at');
            $table->comment('Current user wallet balances and aggregates.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
