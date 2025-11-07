<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            // Personal Information (encrypted)
            $table->text('ssn_encrypted')->nullable()->after('tax_id');
            $table->text('state_tax_id_encrypted')->nullable()->after('ssn_encrypted');

            // Address Information
            $table->string('address_line_1')->nullable()->after('state');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code', 10)->nullable();

            // Filing Status
            $table->enum('filing_status', ['single', 'married_joint', 'married_separate', 'head_of_household', 'qualifying_widow'])->default('single');
            $table->integer('dependents_count')->default(0);
            $table->json('dependents_data')->nullable(); // Store dependent details

            // Business Details
            $table->enum('accounting_method', ['cash', 'accrual'])->default('cash');
            $table->date('business_start_date')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->text('business_description')->nullable();
            $table->string('naics_code', 10)->nullable(); // North American Industry Classification System

            // Banking Information (for refunds/payments)
            $table->text('bank_routing_encrypted')->nullable();
            $table->text('bank_account_encrypted')->nullable();
            $table->enum('bank_account_type', ['checking', 'savings'])->nullable();

            // Tax Professional Information
            $table->boolean('has_tax_professional')->default(false);
            $table->string('tax_pro_name')->nullable();
            $table->string('tax_pro_ptin')->nullable(); // Preparer Tax Identification Number
            $table->string('tax_pro_phone')->nullable();
            $table->string('tax_pro_email')->nullable();

            // Filing Preferences
            $table->boolean('e_file_enabled')->default(true);
            $table->boolean('auto_file_enabled')->default(false);
            $table->boolean('paper_file_backup')->default(false);

            // Compliance Tracking
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_verified')->default(false);

            // API Integration Status
            $table->string('irs_etin')->nullable(); // Electronic Transmitter Identification Number
            $table->json('state_registrations')->nullable(); // Track state tax registrations
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ssn_encrypted',
                'state_tax_id_encrypted',
                'address_line_1',
                'address_line_2',
                'city',
                'zip_code',
                'filing_status',
                'dependents_count',
                'dependents_data',
                'accounting_method',
                'business_start_date',
                'business_phone',
                'business_email',
                'business_description',
                'naics_code',
                'bank_routing_encrypted',
                'bank_account_encrypted',
                'bank_account_type',
                'has_tax_professional',
                'tax_pro_name',
                'tax_pro_ptin',
                'tax_pro_phone',
                'tax_pro_email',
                'e_file_enabled',
                'auto_file_enabled',
                'paper_file_backup',
                'profile_completed_at',
                'last_verified_at',
                'is_verified',
                'irs_etin',
                'state_registrations',
            ]);
        });
    }
};
