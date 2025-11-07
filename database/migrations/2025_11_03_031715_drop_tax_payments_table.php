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
        // Drop tax_payments table as users will pay taxes externally
        // They will receive notifications when payments are due instead
        Schema::dropIfExists('tax_payments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate table if migration is rolled back
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('tax_year');
            $table->enum('payment_type', [
                'balance_due',
                'estimated_q1',
                'estimated_q2',
                'estimated_q3',
                'estimated_q4',
                'extension',
                'amendment'
            ]);
            $table->enum('tax_authority', ['irs', 'state']);
            $table->decimal('amount', 10, 2);
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->text('payment_account_encrypted')->nullable();
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('processed_date')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['scheduled', 'processing', 'completed', 'failed', 'cancelled'])->default('scheduled');
            $table->string('status_message')->nullable();
            $table->string('confirmation_number')->nullable();
            $table->string('payment_gateway_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('interest_amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
