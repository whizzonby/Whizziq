<?php

namespace Database\Seeders;

use App\Models\BusinessMetric;
use App\Models\CashFlowHistory;
use App\Models\Expense;
use App\Models\MarketingMetric;
use App\Models\RevenueSource;
use App\Models\RiskAssessment;
use App\Models\StaffMetric;
use App\Models\SwotAnalysis;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BusinessAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $today = Carbon::today();

        // Seed Business Metrics for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);

            BusinessMetric::create([
                'user_id' => $user->id,
                'date' => $date,
                'revenue' => rand(40000, 60000),
                'profit' => rand(8000, 15000),
                'expenses' => rand(30000, 45000),
                'cash_flow' => rand(12000, 20000),
                'revenue_change_percentage' => rand(-5, 10) / 10,
                'profit_change_percentage' => rand(-8, 12) / 10,
                'expenses_change_percentage' => rand(-3, 5) / 10,
                'cash_flow_change_percentage' => rand(-6, 15) / 10,
            ]);
        }

        // Seed Cash Flow History for last 6 months
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        foreach ($months as $index => $month) {
            $date = $today->copy()->subMonths(5 - $index);

            CashFlowHistory::create([
                'user_id' => $user->id,
                'date' => $date,
                'amount' => rand(10000, 20000),
                'month_label' => $month,
            ]);
        }

        // Seed Revenue Sources for today
        $sources = [
            ['source' => 'online_sales', 'amount' => 32000, 'percentage' => 32],
            ['source' => 'custom_orders', 'amount' => 32000, 'percentage' => 32],
            ['source' => 'subscriptions', 'amount' => 20000, 'percentage' => 20],
            ['source' => 'consulting', 'amount' => 16000, 'percentage' => 16],
        ];

        foreach ($sources as $source) {
            RevenueSource::create([
                'user_id' => $user->id,
                'date' => $today,
                'source' => $source['source'],
                'amount' => $source['amount'],
                'percentage' => $source['percentage'],
            ]);
        }

        // Seed Expenses for today
        $expenses = [
            ['category' => 'salaries', 'amount' => 4700, 'description' => 'Employee salaries and benefits'],
            ['category' => 'advertising', 'amount' => 5200, 'description' => 'Marketing and advertising costs'],
            ['category' => 'rent', 'amount' => 2000, 'description' => 'Office space rental'],
            ['category' => 'supplies', 'amount' => 2100, 'description' => 'Office supplies and materials'],
            ['category' => 'utilities', 'amount' => 1500, 'description' => 'Electricity, water, internet'],
        ];

        foreach ($expenses as $expense) {
            Expense::create([
                'user_id' => $user->id,
                'date' => $today,
                'category' => $expense['category'],
                'amount' => $expense['amount'],
                'description' => $expense['description'],
            ]);
        }

        // Seed SWOT Analysis
        $swotData = [
            ['type' => 'strength', 'description' => 'Strong cash flow', 'priority' => 5],
            ['type' => 'strength', 'description' => 'Efficient supply chain', 'priority' => 4],
            ['type' => 'strength', 'description' => 'Strong customer loyalty', 'priority' => 5],
            ['type' => 'weakness', 'description' => 'High rent cost', 'priority' => 4],
            ['type' => 'weakness', 'description' => 'Limited product range', 'priority' => 3],
            ['type' => 'weakness', 'description' => 'Dependence on key supplier', 'priority' => 4],
            ['type' => 'opportunity', 'description' => 'Expand into new markets', 'priority' => 5],
            ['type' => 'opportunity', 'description' => 'Increase online presence', 'priority' => 4],
            ['type' => 'opportunity', 'description' => 'Rising material costs', 'priority' => 4],
            ['type' => 'threat', 'description' => 'Intense market competition', 'priority' => 5],
            ['type' => 'threat', 'description' => 'Economic downturn risks', 'priority' => 3],
        ];

        foreach ($swotData as $swot) {
            SwotAnalysis::create([
                'user_id' => $user->id,
                'type' => $swot['type'],
                'description' => $swot['description'],
                'priority' => $swot['priority'],
            ]);
        }

        // Seed Risk Assessment
        RiskAssessment::create([
            'user_id' => $user->id,
            'date' => $today,
            'risk_score' => 44,
            'risk_level' => 'moderate',
            'loan_worthiness' => 81,
            'loan_worthiness_level' => 'good',
            'risk_factors' => [
                'Depreciation risks',
                'High dependency on single revenue stream',
                'Limited cash reserves',
            ],
        ]);

        // Seed Staff Metrics
        StaffMetric::create([
            'user_id' => $user->id,
            'date' => $today,
            'total_employees' => 18,
            'churn_rate' => 4.3,
            'employee_turnover' => 11.28,
            'demographics' => [
                'departments' => ['Sales' => 5, 'Engineering' => 8, 'Marketing' => 3, 'Admin' => 2],
                'age_groups' => ['18-25' => 2, '26-35' => 10, '36-45' => 4, '46+' => 2],
            ],
        ]);

        // Seed Enhanced Marketing Metrics with Conversion Funnels
        $marketingData = [
            [
                'platform' => 'facebook',
                'channel' => 'facebook',
                'followers' => 4300,
                'engagement' => 180,
                'reach' => 8900,
                'awareness' => 15000,
                'leads' => 1200,
                'conversions' => 85,
                'retention_count' => 60,
                'cost_per_click' => 1.25,
                'cost_per_conversion' => 45.00,
                'ad_spend' => 3825,
                'clicks' => 3060,
                'conversion_rate' => 2.78,
                'engagement_rate' => 4.19,
                'customer_lifetime_value' => 450.00,
                'customer_acquisition_cost' => 45.00,
                'clv_cac_ratio' => 10.00,
                'impressions' => 45000,
                'roi' => 256.50,
            ],
            [
                'platform' => 'google',
                'channel' => 'google',
                'followers' => 0,
                'engagement' => 0,
                'reach' => 12500,
                'awareness' => 18000,
                'leads' => 950,
                'conversions' => 120,
                'retention_count' => 85,
                'cost_per_click' => 2.50,
                'cost_per_conversion' => 58.00,
                'ad_spend' => 6960,
                'clicks' => 2784,
                'conversion_rate' => 4.31,
                'engagement_rate' => 0,
                'customer_lifetime_value' => 580.00,
                'customer_acquisition_cost' => 58.00,
                'clv_cac_ratio' => 10.00,
                'impressions' => 85000,
                'roi' => 320.50,
            ],
            [
                'platform' => 'linkedin',
                'channel' => 'linkedin',
                'followers' => 1750,
                'engagement' => 95,
                'reach' => 3200,
                'awareness' => 5000,
                'leads' => 380,
                'conversions' => 45,
                'retention_count' => 38,
                'cost_per_click' => 3.80,
                'cost_per_conversion' => 32.00,
                'ad_spend' => 1440,
                'clicks' => 379,
                'conversion_rate' => 11.87,
                'engagement_rate' => 5.42,
                'customer_lifetime_value' => 850.00,
                'customer_acquisition_cost' => 32.00,
                'clv_cac_ratio' => 26.56,
                'impressions' => 18000,
                'roi' => 425.00,
            ],
            [
                'platform' => 'instagram',
                'channel' => 'organic',
                'followers' => 5440,
                'engagement' => 320,
                'reach' => 12500,
                'awareness' => 12500,
                'leads' => 450,
                'conversions' => 22,
                'retention_count' => 18,
                'cost_per_click' => 0,
                'cost_per_conversion' => 0,
                'ad_spend' => 0,
                'clicks' => 850,
                'conversion_rate' => 2.59,
                'engagement_rate' => 5.88,
                'customer_lifetime_value' => 380.00,
                'customer_acquisition_cost' => 0,
                'clv_cac_ratio' => 0,
                'impressions' => 35000,
                'roi' => 0,
            ],
        ];

        foreach ($marketingData as $marketing) {
            MarketingMetric::create([
                'user_id' => $user->id,
                'date' => $today,
                'platform' => $marketing['platform'],
                'channel' => $marketing['channel'],
                'followers' => $marketing['followers'],
                'engagement' => $marketing['engagement'],
                'reach' => $marketing['reach'],
                'awareness' => $marketing['awareness'],
                'leads' => $marketing['leads'],
                'conversions' => $marketing['conversions'],
                'retention_count' => $marketing['retention_count'],
                'cost_per_click' => $marketing['cost_per_click'],
                'cost_per_conversion' => $marketing['cost_per_conversion'],
                'ad_spend' => $marketing['ad_spend'],
                'clicks' => $marketing['clicks'],
                'conversion_rate' => $marketing['conversion_rate'],
                'engagement_rate' => $marketing['engagement_rate'],
                'customer_lifetime_value' => $marketing['customer_lifetime_value'],
                'customer_acquisition_cost' => $marketing['customer_acquisition_cost'],
                'clv_cac_ratio' => $marketing['clv_cac_ratio'],
                'impressions' => $marketing['impressions'],
                'roi' => $marketing['roi'],
            ]);
        }

        $this->command->info('Business Analytics data seeded successfully!');
    }
}
