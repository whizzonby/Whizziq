<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_profile_calculation()
    {
        $user = User::factory()->create();
        
        $businessProfile = BusinessProfile::create([
            'user_id' => $user->id,
            'biz_registered_name' => 'Test Business Ltd',
            'biz_country' => 'US',
            'biz_incorporation_date' => '2020-01-01',
            'biz_legal_type' => 'llc',
            'ops_employee_count' => 5,
            'rev_monthly_avg' => 50000,
            'rev_yoy_change' => 15,
            'exp_fixed_monthly' => 10000,
            'exp_variable_monthly' => 15000,
            'exp_payroll' => 20000,
            'exp_marketing' => 5000,
            'exp_loans' => 2000,
            'hr_full_time' => 4,
            'hr_part_time' => 1,
            'mkt_budget' => 5000,
            'mkt_followers' => [
                ['platform' => 'instagram', 'count' => 1000],
                ['platform' => 'linkedin', 'count' => 500],
            ],
            'fin_bank_balance' => 100000,
            'fin_debt_amount' => 50000,
            'comp_tax_cycle' => 'quarterly',
        ]);

        // Calculate metrics
        $businessProfile->calculateMetrics();

        // Assert calculations
        $this->assertEquals(5000, $businessProfile->monthly_net_profit); // 50000 - 52000
        $this->assertEquals(10, $businessProfile->profit_margin); // (5000/50000) * 100
        $this->assertEquals(10000, $businessProfile->employee_productivity); // 50000 / 5
        $this->assertEquals(10, $businessProfile->marketing_roi); // 50000 / 5000
        $this->assertEquals(1.92, round($businessProfile->runway_months, 2)); // 100000 / 52000
        $this->assertEquals(2, $businessProfile->current_ratio); // 100000 / 50000
        $this->assertGreaterThan(0, $businessProfile->visibility_index);
        $this->assertGreaterThan(0, $businessProfile->growth_score);
    }

    public function test_business_age_calculation()
    {
        $user = User::factory()->create();
        
        $businessProfile = BusinessProfile::create([
            'user_id' => $user->id,
            'biz_registered_name' => 'Test Business',
            'biz_country' => 'US',
            'biz_incorporation_date' => '2020-01-01',
            'biz_legal_type' => 'llc',
            'comp_tax_cycle' => 'quarterly',
        ]);

        $this->assertEquals(4, $businessProfile->business_age);
    }

    public function test_growth_status()
    {
        $user = User::factory()->create();
        
        $businessProfile = BusinessProfile::create([
            'user_id' => $user->id,
            'biz_registered_name' => 'Test Business',
            'biz_country' => 'US',
            'biz_incorporation_date' => '2020-01-01',
            'biz_legal_type' => 'llc',
            'rev_yoy_change' => 15,
            'comp_tax_cycle' => 'quarterly',
        ]);

        $this->assertEquals('Growing', $businessProfile->growth_status);
    }

    public function test_financial_health()
    {
        $user = User::factory()->create();
        
        $businessProfile = BusinessProfile::create([
            'user_id' => $user->id,
            'biz_registered_name' => 'Test Business',
            'biz_country' => 'US',
            'biz_incorporation_date' => '2020-01-01',
            'biz_legal_type' => 'llc',
            'current_ratio' => 2.5,
            'profit_margin' => 20,
            'comp_tax_cycle' => 'quarterly',
        ]);

        $this->assertEquals('Excellent', $businessProfile->financial_health);
    }
}


