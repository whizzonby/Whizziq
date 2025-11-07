<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'biz_registered_name',
        'biz_trading_name',
        'biz_country',
        'biz_tax_id',
        'biz_incorporation_date',
        'biz_legal_type',
        'ops_employee_count',
        'ops_location',
        'ops_hours',
        'ops_systems',
        'rev_monthly_avg',
        'rev_yoy_change',
        'rev_payment_methods',
        'rev_channels',
        'rev_top_customers',
        'exp_fixed_monthly',
        'exp_variable_monthly',
        'exp_payroll',
        'exp_marketing',
        'exp_loans',
        'hr_full_time',
        'hr_part_time',
        'hr_avg_salary',
        'hr_roles',
        'hr_contractors',
        'mkt_platforms',
        'mkt_followers',
        'mkt_budget',
        'mkt_traffic',
        'mkt_bounce_rate',
        'comp_tax_cycle',
        'comp_licenses',
        'comp_bookkeeping_type',
        'comp_accountant_name',
        'fin_ar_days',
        'fin_ap_days',
        'fin_bank_balance',
        'fin_debt_amount',
        'strat_goals',
        'strat_investments',
        'strat_challenges',
        'prefs_insight_freq',
        'prefs_report_format',
        'prefs_detail_level',
        'prefs_ai_actions',
        'monthly_net_profit',
        'profit_margin',
        'employee_productivity',
        'marketing_roi',
        'runway_months',
        'current_ratio',
        'visibility_index',
        'growth_score',
    ];

    protected $casts = [
        'biz_incorporation_date' => 'date',
        'ops_systems' => 'array',
        'rev_payment_methods' => 'array',
        'rev_channels' => 'array',
        'rev_top_customers' => 'array',
        'hr_roles' => 'array',
        'mkt_platforms' => 'array',
        'mkt_followers' => 'array',
        'comp_licenses' => 'array',
        'strat_goals' => 'array',
        'strat_investments' => 'array',
        'strat_challenges' => 'array',
        'prefs_ai_actions' => 'boolean',
        'rev_monthly_avg' => 'decimal:2',
        'rev_yoy_change' => 'decimal:2',
        'exp_fixed_monthly' => 'decimal:2',
        'exp_variable_monthly' => 'decimal:2',
        'exp_payroll' => 'decimal:2',
        'exp_marketing' => 'decimal:2',
        'exp_loans' => 'decimal:2',
        'hr_avg_salary' => 'decimal:2',
        'mkt_budget' => 'decimal:2',
        'mkt_traffic' => 'decimal:0',
        'mkt_bounce_rate' => 'decimal:2',
        'fin_bank_balance' => 'decimal:2',
        'fin_debt_amount' => 'decimal:2',
        'monthly_net_profit' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'employee_productivity' => 'decimal:2',
        'marketing_roi' => 'decimal:2',
        'runway_months' => 'decimal:2',
        'current_ratio' => 'decimal:2',
        'visibility_index' => 'decimal:2',
        'growth_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate all business metrics based on the profile data
     */
    public function calculateMetrics(): void
    {
        // Calculate total monthly expenses
        $totalMonthlyExpenses = $this->exp_fixed_monthly + $this->exp_variable_monthly + 
                               $this->exp_payroll + $this->exp_marketing + $this->exp_loans;

        // Calculate monthly net profit
        $monthlyNetProfit = $this->rev_monthly_avg - $totalMonthlyExpenses;

        // Calculate profit margin
        $profitMargin = $this->rev_monthly_avg > 0 ? ($monthlyNetProfit / $this->rev_monthly_avg) * 100 : 0;

        // Calculate employee productivity
        $totalStaff = $this->hr_full_time + $this->hr_part_time;
        $employeeProductivity = $totalStaff > 0 ? $this->rev_monthly_avg / $totalStaff : 0;

        // Calculate marketing ROI
        $marketingROI = $this->mkt_budget > 0 ? $this->rev_monthly_avg / $this->mkt_budget : 0;

        // Calculate runway
        $runwayMonths = $totalMonthlyExpenses > 0 ? $this->fin_bank_balance / $totalMonthlyExpenses : 0;

        // Calculate current ratio
        $currentRatio = $this->fin_debt_amount > 0 ? $this->fin_bank_balance / $this->fin_debt_amount : 0;

        // Calculate visibility index
        $totalFollowers = array_sum($this->mkt_followers ?? []);
        $visibilityIndex = $this->mkt_budget > 0 ? min(100, ($totalFollowers / $this->mkt_budget) * 100) : 0;

        // Calculate growth score (weighted combination)
        $growthScore = $this->calculateGrowthScore($profitMargin, $this->rev_yoy_change, $visibilityIndex);

        // Update calculated fields
        $this->update([
            'monthly_net_profit' => $monthlyNetProfit,
            'profit_margin' => $profitMargin,
            'employee_productivity' => $employeeProductivity,
            'marketing_roi' => $marketingROI,
            'runway_months' => $runwayMonths,
            'current_ratio' => $currentRatio,
            'visibility_index' => $visibilityIndex,
            'growth_score' => $growthScore,
        ]);
    }

    /**
     * Calculate growth score based on multiple factors
     */
    private function calculateGrowthScore(float $profitMargin, float $yoyChange, float $visibilityIndex): float
    {
        // Weighted scoring: 40% profit margin, 40% YoY growth, 20% visibility
        $marginScore = min(100, max(0, $profitMargin * 2)); // Scale margin to 0-100
        $growthScore = min(100, max(0, 50 + $yoyChange)); // Scale YoY to 0-100
        $visibilityScore = min(100, $visibilityIndex);

        return ($marginScore * 0.4) + ($growthScore * 0.4) + ($visibilityScore * 0.2);
    }

    /**
     * Get business age in years
     */
    public function getBusinessAgeAttribute(): int
    {
        return $this->biz_incorporation_date->diffInYears(now());
    }

    /**
     * Get total staff count
     */
    public function getTotalStaffAttribute(): int
    {
        return $this->hr_full_time + $this->hr_part_time;
    }

    /**
     * Get growth status based on YoY change
     */
    public function getGrowthStatusAttribute(): string
    {
        if ($this->rev_yoy_change >= 10) return 'Growing';
        if ($this->rev_yoy_change < 0) return 'Declining';
        return 'Stable';
    }

    /**
     * Get financial health status
     */
    public function getFinancialHealthAttribute(): string
    {
        if ($this->current_ratio >= 2.0 && $this->profit_margin >= 15) return 'Excellent';
        if ($this->current_ratio >= 1.5 && $this->profit_margin >= 10) return 'Good';
        if ($this->current_ratio >= 1.0 && $this->profit_margin >= 5) return 'Fair';
        return 'Needs Attention';
    }
}
