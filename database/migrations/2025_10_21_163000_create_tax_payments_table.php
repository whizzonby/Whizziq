<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_filing_id')->nullable()->constrained()->onDelete('set null');

            // Payment Details
            $table->integer('tax_year');
            $table->enum('payment_type', [
                'balance_due', 'estimated_q1', 'estimated_q2',
                'estimated_q3', 'estimated_q4', 'extension', 'amendment'
            ]);
            $table->enum('tax_authority', ['irs', 'state'])->default('irs');

            // Amount Information
            $table->decimal('amount', 15, 2);
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount + processing_fee

            // Payment Method
            $table->enum('payment_method', ['ach', 'credit_card', 'debit_card', 'check', 'wire']);
            $table->text('payment_account_encrypted')->nullable(); // Last 4 digits or account info

            // Scheduling
            $table->timestamp('scheduled_date');
            $table->timestamp('processed_date')->nullable();
            $table->date('due_date');

            // Status Tracking
            $table->enum('status', ['scheduled', 'processing', 'completed', 'failed', 'cancelled'])->default('scheduled');
            $table->text('status_message')->nullable();

            // Confirmation
            $table->string('confirmation_number')->nullable()->unique();
            $table->string('payment_gateway_id')->nullable(); // Stripe, Plaid, etc.
            $table->json('gateway_response')->nullable();

            // Penalties and Interest
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('interest_amount', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'tax_year']);
            $table->index(['status', 'scheduled_date']);
            $table->index('confirmation_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_payments');
    }
};
