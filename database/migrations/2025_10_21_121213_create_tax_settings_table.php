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
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Business Info
            $table->string('business_name')->nullable();
            $table->string('tax_id')->nullable(); // EIN, SSN, VAT number
            $table->enum('business_type', ['sole_proprietor', 'llc', 's_corp', 'c_corp', 'partnership'])->default('sole_proprietor');

            // Tax Configuration
            $table->string('country', 2)->default('US'); // US, CA, GB, etc.
            $table->string('state')->nullable(); // For US/Canada
            $table->date('fiscal_year_end')->nullable(); // e.g., 2024-12-31
            $table->enum('filing_frequency', ['quarterly', 'annual'])->default('annual');
            $table->decimal('tax_rate', 5, 2)->nullable(); // Estimated tax rate (e.g., 25.00%)

            // Settings
            $table->boolean('auto_categorize')->default(true);
            $table->boolean('reminder_enabled')->default(true);
            $table->integer('reminder_days_before')->default(30);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
