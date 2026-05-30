<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->text('token')->nullable();
            $table->string('proxy')->nullable();
            $table->enum('status', ['active', 'inactive', 'error'])->default('active')->index();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['bank_name', 'account_number']);
            $table->comment('Connected source bank accounts for synchronization.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
