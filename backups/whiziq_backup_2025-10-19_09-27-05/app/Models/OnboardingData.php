<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingData extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_city',
        'user_country',
        'founder_stage',
        'biz_name',
        'biz_type',
        'industry_raw',
        'industry_code',
        'mission_text',
        'biz_stage',
        'items',
        'rent',
        'utilities_software',
        'marketing',
        'staff',
        'setup_one_time',
        'total_available',
        'expected_monthly_income',
        'payment_terms',
        'expected_breakeven_month',
        'capital_source',
        'marketing_channels',
        'social_handles',
        'website_url',
        'audience_type',
        'audience_age',
        'team_mode',
        'team_size',
        'team_roles',
        'finance_skill',
        'ai_tone',
        'insight_frequency',
        'auto_email_reports',
        'total_net_revenue',
        'monthly_burn',
        'runway_months',
        'weighted_margin_rate',
        'visibility_score',
        'health_ring_score',
    ];

    protected $casts = [
        'items' => 'array',
        'marketing_channels' => 'array',
        'social_handles' => 'array',
        'team_roles' => 'array',
        'auto_email_reports' => 'boolean',
        'rent' => 'decimal:2',
        'utilities_software' => 'decimal:2',
        'marketing' => 'decimal:2',
        'staff' => 'decimal:2',
        'setup_one_time' => 'decimal:2',
        'total_available' => 'decimal:2',
        'expected_monthly_income' => 'decimal:2',
        'total_net_revenue' => 'decimal:2',
        'monthly_burn' => 'decimal:2',
        'runway_months' => 'decimal:2',
        'weighted_margin_rate' => 'decimal:2',
        'visibility_score' => 'decimal:2',
        'health_ring_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate financial metrics based on the onboarding data
     */
    public function calculateMetrics(): void
    {
        $items = $this->items ?? [];
        $totalNetRevenue = 0;
        $totalGrossRevenue = 0;
        $totalCost = 0;

        // Calculate revenue from items
        foreach ($items as $item) {
            $price = $item['price'] ?? 0;
            $cost = $item['cost'] ?? 0;
            $units = $item['units_per_month'] ?? 0;
            $refundRate = ($item['refund_rate'] ?? 0) / 100;
            $discountRate = ($item['discount_rate'] ?? 0) / 100;

            $grossRevenue = $price * $units;
            $refundImpact = $grossRevenue * $refundRate;
            $discountImpact = $grossRevenue * $discountRate;
            $netRevenue = $grossRevenue - $refundImpact - $discountImpact;

            $totalNetRevenue += $netRevenue;
            $totalGrossRevenue += $grossRevenue;
            $totalCost += $cost * $units;
        }

        // Calculate monthly burn
        $monthlyBurn = $this->rent + $this->utilities_software + $this->marketing + $this->staff;
        $amortizedSetup = $this->setup_one_time / 3;
        $effectiveMonthlyBurn = $monthlyBurn + $amortizedSetup;

        // Calculate runway
        $runwayMonths = $this->total_available > 0 ? $this->total_available / max($effectiveMonthlyBurn, 0.01) : 0;

        // Calculate weighted margin rate
        $weightedMarginRate = $totalGrossRevenue > 0 ? (($totalGrossRevenue - $totalCost) / $totalGrossRevenue) * 100 : 0;

        // Calculate visibility score (simplified)
        $channelCount = count($this->marketing_channels ?? []);
        $visibilityScore = min(100, ($channelCount * 20) + (count($this->social_handles ?? []) * 10));

        // Calculate health ring score
        $liquidityScore = min(100, ($this->total_available / max($effectiveMonthlyBurn, 0.01)) * 20);
        $marginScore = min(100, $weightedMarginRate);
        $runwayScore = min(100, $runwayMonths * 10);
        $visibilityScore = min(100, $visibilityScore);

        $healthRingScore = ($liquidityScore * 0.3) + ($marginScore * 0.3) + ($runwayScore * 0.2) + ($visibilityScore * 0.2);

        // Update calculated fields
        $this->update([
            'total_net_revenue' => $totalNetRevenue,
            'monthly_burn' => $effectiveMonthlyBurn,
            'runway_months' => $runwayMonths,
            'weighted_margin_rate' => $weightedMarginRate,
            'visibility_score' => $visibilityScore,
            'health_ring_score' => $healthRingScore,
        ]);
    }
}
