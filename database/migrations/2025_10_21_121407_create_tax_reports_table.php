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
        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_period_id')->nullable()->constrained('tax_periods')->nullOnDelete();

            // Report Details
            $table->string('report_name'); // e.g., "2024 Annual Tax Summary"
            $table->date('report_date')->default(now());
            $table->date('period_start');
            $table->date('period_end');

            // Financial Summary
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('estimated_tax', 15, 2)->default(0);

            // Report File
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_reports');
    }
};
