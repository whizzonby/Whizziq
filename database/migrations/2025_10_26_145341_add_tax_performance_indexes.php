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
        // Add performance indexes for tax-related queries
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['user_id', 'is_tax_deductible', 'date'], 'idx_expenses_tax_deductible');
            $table->index(['user_id', 'tax_category_id'], 'idx_expenses_tax_category');
        });

        Schema::table('tax_filings', function (Blueprint $table) {
            $table->index(['user_id', 'tax_year', 'status'], 'idx_tax_filings_year_status');
            $table->index(['user_id', 'filing_type'], 'idx_tax_filings_type');
        });

        Schema::table('tax_payments', function (Blueprint $table) {
            $table->index(['user_id', 'due_date', 'status'], 'idx_tax_payments_due_status');
            $table->index(['user_id', 'payment_type'], 'idx_tax_payments_type');
            $table->index(['user_id', 'tax_year'], 'idx_tax_payments_year');
        });

        Schema::table('tax_documents', function (Blueprint $table) {
            $table->index(['user_id', 'tax_year', 'document_type'], 'idx_tax_docs_year_type');
            $table->index(['user_id', 'verification_status'], 'idx_tax_docs_verification');
        });

        Schema::table('tax_periods', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_tax_periods_status');
            $table->index(['user_id', 'filing_deadline'], 'idx_tax_periods_deadline');
        });

        Schema::table('tax_reports', function (Blueprint $table) {
            $table->index(['user_id', 'period_start', 'period_end'], 'idx_tax_reports_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_expenses_tax_deductible');
            $table->dropIndex('idx_expenses_tax_category');
        });

        Schema::table('tax_filings', function (Blueprint $table) {
            $table->dropIndex('idx_tax_filings_year_status');
            $table->dropIndex('idx_tax_filings_type');
        });

        Schema::table('tax_payments', function (Blueprint $table) {
            $table->dropIndex('idx_tax_payments_due_status');
            $table->dropIndex('idx_tax_payments_type');
            $table->dropIndex('idx_tax_payments_year');
        });

        Schema::table('tax_documents', function (Blueprint $table) {
            $table->dropIndex('idx_tax_docs_year_type');
            $table->dropIndex('idx_tax_docs_verification');
        });

        Schema::table('tax_periods', function (Blueprint $table) {
            $table->dropIndex('idx_tax_periods_status');
            $table->dropIndex('idx_tax_periods_deadline');
        });

        Schema::table('tax_reports', function (Blueprint $table) {
            $table->dropIndex('idx_tax_reports_period');
        });
    }
};
