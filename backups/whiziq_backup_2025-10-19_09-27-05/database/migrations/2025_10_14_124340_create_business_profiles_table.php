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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // 1. Business Profile & Registration Data
            $table->string('biz_registered_name');
            $table->string('biz_trading_name')->nullable();
            $table->string('biz_country');
            $table->string('biz_tax_id')->nullable();
            $table->date('biz_incorporation_date');
            $table->string('biz_legal_type');
            
            // 2. Business Operations Snapshot
            $table->integer('ops_employee_count')->default(0);
            $table->string('ops_location')->nullable();
            $table->string('ops_hours')->nullable();
            $table->json('ops_systems')->nullable();
            
            // 3. Revenue & Sales Channels
            $table->decimal('rev_monthly_avg', 12, 2)->default(0);
            $table->decimal('rev_yoy_change', 5, 2)->default(0);
            $table->json('rev_payment_methods')->nullable();
            $table->json('rev_channels')->nullable();
            $table->json('rev_top_customers')->nullable();
            
            // 4. Expense & Cost Structure
            $table->decimal('exp_fixed_monthly', 12, 2)->default(0);
            $table->decimal('exp_variable_monthly', 12, 2)->default(0);
            $table->decimal('exp_payroll', 12, 2)->default(0);
            $table->decimal('exp_marketing', 12, 2)->default(0);
            $table->decimal('exp_loans', 12, 2)->default(0);
            
            // 5. Human Resources Snapshot
            $table->integer('hr_full_time')->default(0);
            $table->integer('hr_part_time')->default(0);
            $table->decimal('hr_avg_salary', 10, 2)->default(0);
            $table->json('hr_roles')->nullable();
            $table->text('hr_contractors')->nullable();
            
            // 6. Marketing & Digital Presence
            $table->json('mkt_platforms')->nullable();
            $table->json('mkt_followers')->nullable();
            $table->decimal('mkt_budget', 10, 2)->default(0);
            $table->decimal('mkt_traffic', 10, 0)->nullable();
            $table->decimal('mkt_bounce_rate', 5, 2)->nullable();
            
            // 7. Systems & Compliance
            $table->string('comp_tax_cycle');
            $table->json('comp_licenses')->nullable();
            $table->string('comp_bookkeeping_type')->nullable();
            $table->string('comp_accountant_name')->nullable();
            
            // 8. Financial Health Indicators
            $table->integer('fin_ar_days')->nullable();
            $table->integer('fin_ap_days')->nullable();
            $table->decimal('fin_bank_balance', 12, 2)->nullable();
            $table->decimal('fin_debt_amount', 12, 2)->nullable();
            
            // 9. Strategy & Growth Plans
            $table->json('strat_goals')->nullable();
            $table->json('strat_investments')->nullable();
            $table->json('strat_challenges')->nullable();
            
            // 10. Owner Preferences & AI Settings
            $table->string('prefs_insight_freq')->default('weekly');
            $table->string('prefs_report_format')->default('interactive');
            $table->string('prefs_detail_level')->default('basic');
            $table->boolean('prefs_ai_actions')->default(true);
            
            // Calculated metrics
            $table->decimal('monthly_net_profit', 12, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->decimal('employee_productivity', 12, 2)->default(0);
            $table->decimal('marketing_roi', 5, 2)->default(0);
            $table->decimal('runway_months', 8, 2)->default(0);
            $table->decimal('current_ratio', 5, 2)->default(0);
            $table->decimal('visibility_index', 5, 2)->default(0);
            $table->decimal('growth_score', 5, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
