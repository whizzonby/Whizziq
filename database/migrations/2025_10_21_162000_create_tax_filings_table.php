<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_period_id')->nullable()->constrained()->onDelete('set null');

            // Filing Information
            $table->integer('tax_year');
            $table->enum('filing_type', ['original', 'amended', 'extension'])->default('original');
            $table->enum('filing_method', ['e_file', 'paper', 'tax_professional'])->default('e_file');

            // Status Tracking
            $table->enum('status', [
                'draft', 'ready', 'submitted', 'pending', 'accepted',
                'rejected', 'amended', 'completed'
            ])->default('draft');
            $table->text('status_message')->nullable();

            // Submission Details
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Confirmation and Tracking
            $table->string('federal_confirmation_number')->nullable()->unique();
            $table->string('state_confirmation_number')->nullable();
            $table->json('api_response')->nullable(); // Store full API response

            // Tax Amounts
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('federal_withholding', 15, 2)->default(0);
            $table->decimal('estimated_payments', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('amount_owed', 15, 2)->default(0);

            // State Tax Amounts
            $table->decimal('state_taxable_income', 15, 2)->default(0);
            $table->decimal('state_tax', 15, 2)->default(0);
            $table->decimal('state_withholding', 15, 2)->default(0);
            $table->decimal('state_refund', 15, 2)->default(0);
            $table->decimal('state_owed', 15, 2)->default(0);

            // Forms Generated
            $table->json('forms_included')->nullable(); // List of forms filed
            $table->json('pdf_paths')->nullable(); // Paths to generated PDFs

            // Payment Information
            $table->enum('payment_method', ['direct_debit', 'credit_card', 'check', 'other'])->nullable();
            $table->timestamp('payment_scheduled_at')->nullable();
            $table->timestamp('payment_processed_at')->nullable();
            $table->string('payment_confirmation')->nullable();

            // Audit Trail
            $table->string('prepared_by')->nullable(); // User who prepared
            $table->string('reviewed_by')->nullable(); // Tax professional who reviewed
            $table->json('calculation_details')->nullable(); // Detailed calculation breakdown
            $table->json('audit_log')->nullable(); // Track all changes

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'tax_year']);
            $table->index(['status', 'submitted_at']);
            $table->index('federal_confirmation_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_filings');
    }
};
