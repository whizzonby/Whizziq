<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add performance indexes for dashboard widget queries
     */
    public function up(): void
    {
        // Add composite index for ClientPayment queries used in dashboard widgets
        // This optimizes queries that filter by user_id and payment_date together
        Schema::table('client_payments', function (Blueprint $table) {
            try {
                $table->index(['user_id', 'payment_date'], 'idx_client_payments_user_date');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        });

        // BusinessMetric already has index(['user_id', 'date']) from original migration
        // RevenueSource already has index(['user_id', 'date', 'source']) from original migration  
        // Expenses already has index(['user_id', 'date', 'category']) from original migration
        // These existing indexes should be sufficient for our queries
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_client_payments_user_date');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
        });
    }
};

