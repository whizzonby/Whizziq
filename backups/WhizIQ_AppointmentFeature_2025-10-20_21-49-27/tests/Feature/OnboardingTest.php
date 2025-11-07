<?php

namespace Tests\Feature;

use App\Models\OnboardingData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_data_calculation()
    {
        $user = User::factory()->create();
        
        $onboardingData = OnboardingData::create([
            'user_id' => $user->id,
            'user_name' => 'John Doe',
            'user_city' => 'New York',
            'user_country' => 'United States',
            'founder_stage' => 'solo',
            'biz_name' => 'Test Business',
            'biz_type' => 'service',
            'industry_raw' => 'SaaS',
            'industry_code' => '62010',
            'mission_text' => 'To help businesses grow',
            'biz_stage' => 'testing',
            'items' => [
                [
                    'name' => 'Consulting Service',
                    'price' => 100,
                    'cost' => 30,
                    'units_per_month' => 10,
                    'sale_type' => 'monthly',
                    'refund_rate' => 5,
                    'discount_rate' => 10,
                ]
            ],
            'rent' => 1000,
            'utilities_software' => 200,
            'marketing' => 500,
            'staff' => 2000,
            'setup_one_time' => 5000,
            'total_available' => 50000,
            'expected_monthly_income' => 1000,
            'payment_terms' => 'immediate',
            'expected_breakeven_month' => 6,
            'capital_source' => 'personal',
            'marketing_channels' => ['instagram', 'linkedin'],
            'social_handles' => [],
            'website_url' => 'https://test.com',
            'audience_type' => 'b2b',
            'audience_age' => null,
            'team_mode' => 'solo',
            'team_size' => 0,
            'team_roles' => [],
            'finance_skill' => 3,
            'ai_tone' => 'friendly',
            'insight_frequency' => 'weekly',
            'auto_email_reports' => false,
        ]);

        // Calculate metrics
        $onboardingData->calculateMetrics();

        // Assert calculations
        $this->assertEquals(850, $onboardingData->total_net_revenue); // 1000 - 50 (refund) - 100 (discount)
        $this->assertEquals(3700, $onboardingData->monthly_burn); // 1000 + 200 + 500 + 2000 + (5000/3)
        $this->assertEquals(13.51, round($onboardingData->runway_months, 2)); // 50000 / 3700
        $this->assertEquals(70, $onboardingData->weighted_margin_rate); // (1000-300)/1000 * 100
        $this->assertEquals(40, $onboardingData->visibility_score); // 2 channels * 20
        $this->assertGreaterThan(0, $onboardingData->health_ring_score);
    }

    public function test_health_ring_calculation()
    {
        $user = User::factory()->create();
        
        $onboardingData = OnboardingData::create([
            'user_id' => $user->id,
            'user_name' => 'Jane Doe',
            'founder_stage' => 'solo',
            'biz_name' => 'Test Business',
            'biz_type' => 'product',
            'biz_stage' => 'idea',
            'items' => [],
            'rent' => 0,
            'utilities_software' => 0,
            'marketing' => 0,
            'staff' => 0,
            'setup_one_time' => 0,
            'total_available' => 10000,
            'payment_terms' => 'immediate',
            'marketing_channels' => ['instagram'],
            'audience_type' => 'b2c',
            'team_mode' => 'solo',
            'finance_skill' => 3,
            'ai_tone' => 'friendly',
            'insight_frequency' => 'weekly',
            'auto_email_reports' => false,
        ]);

        $onboardingData->calculateMetrics();

        // With no revenue and no burn, health score should be low
        $this->assertEquals(0, $onboardingData->total_net_revenue);
        $this->assertEquals(0, $onboardingData->monthly_burn);
        $this->assertGreaterThan(0, $onboardingData->health_ring_score);
    }
}


