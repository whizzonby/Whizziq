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
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Period Info
            $table->string('name'); // e.g., "Q1 2024", "FY 2024"
            $table->enum('type', ['quarterly', 'annual'])->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('filing_deadline')->nullable();

            // Status
            $table->enum('status', ['active', 'closed', 'filed'])->default('active');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('filed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_periods');
    }
};
