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
        Schema::create('onboarding_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // User Profile
            $table->string('user_name');
            $table->string('user_city')->nullable();
            $table->string('user_country')->nullable();
            $table->string('founder_stage');
            
            // Business Identity
            $table->string('biz_name');
            $table->string('biz_type');
            $table->string('industry_raw')->nullable();
            $table->string('industry_code')->nullable();
            $table->text('mission_text')->nullable();
            $table->string('biz_stage');
            
            // Products/Services (JSON array)
            $table->json('items')->nullable();
            
            // Cost Structure
            $table->decimal('rent', 10, 2)->default(0);
            $table->decimal('utilities_software', 10, 2)->default(0);
            $table->decimal('marketing', 10, 2)->default(0);
            $table->decimal('staff', 10, 2)->default(0);
            $table->decimal('setup_one_time', 10, 2)->default(0);
            $table->decimal('total_available', 10, 2)->default(0);
            
            // Revenue & Cashflow
            $table->decimal('expected_monthly_income', 10, 2)->nullable();
            $table->string('payment_terms');
            $table->integer('expected_breakeven_month')->nullable();
            $table->string('capital_source')->nullable();
            
            // Marketing
            $table->json('marketing_channels')->nullable();
            $table->json('social_handles')->nullable();
            $table->string('website_url')->nullable();
            $table->string('audience_type');
            $table->string('audience_age')->nullable();
            
            // Team
            $table->string('team_mode');
            $table->integer('team_size')->default(0);
            $table->json('team_roles')->nullable();
            
            // Preferences
            $table->integer('finance_skill')->default(3);
            $table->string('ai_tone')->default('friendly');
            $table->string('insight_frequency')->default('weekly');
            $table->boolean('auto_email_reports')->default(false);
            
            // Calculated metrics (computed on save)
            $table->decimal('total_net_revenue', 10, 2)->default(0);
            $table->decimal('monthly_burn', 10, 2)->default(0);
            $table->decimal('runway_months', 8, 2)->default(0);
            $table->decimal('weighted_margin_rate', 5, 2)->default(0);
            $table->decimal('visibility_score', 5, 2)->default(0);
            $table->decimal('health_ring_score', 5, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_data');
    }
};
