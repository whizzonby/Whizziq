<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // quickbooks, xero, stripe, oracle, sap
            $table->text('access_token')->nullable(); // encrypted
            $table->text('refresh_token')->nullable(); // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('sync_status')->default('pending'); // pending, syncing, success, failed
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'platform']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_connections');
    }
};
