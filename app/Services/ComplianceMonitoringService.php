<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\Models\TaxPeriod;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ComplianceMonitoringService
{
    /**
     * Calculate compliance risk score for a user (0-100)
     * Higher score = higher risk
     * Cached for 24 hours
     */
    public function calculateComplianceRiskScore(User $user): array
    {
        $cacheKey = "compliance_risk_score_{$user->id}_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () use ($user) {
            return $this->performRiskCalculation($user);
        });
    }

    /**
     * Clear cache for user's compliance risk score
     */
    public function clearRiskScoreCache(User $user): void
    {
        $cacheKey = "compliance_risk_score_{$user->id}_" . now()->format('Y-m-d');
        Cache::forget($cacheKey);
    }

    /**
     * Perform the actual risk calculation
     */
    protected function performRiskCalculation(User $user): array
    {
        $factors = [];
        $totalScore = 0;

        // Factor 1: Missing tax documentation (0-20 points)
        $documentationScore = $this->assessDocumentationCompleteness($user);
        $factors['documentation_completeness'] = [
            'score' => $documentationScore,
            'weight' => 20,
            'status' => $documentationScore > 10 ? 'high_risk' : 'low_risk',
            'message' => $documentationScore > 10
                ? 'Missing critical tax documentation'
                : 'Tax documentation is complete'
        ];
        $totalScore += $documentationScore;

        // Factor 2: Unusual expense patterns (0-25 points)
        $expenseAnomalyScore = $this->detectExpenseAnomalies($user);
        $factors['expense_anomalies'] = [
            'score' => $expenseAnomalyScore,
            'weight' => 25,
            'status' => $expenseAnomalyScore > 15 ? 'medium_risk' : 'low_risk',
            'message' => $expenseAnomalyScore > 15
                ? 'Unusual expense patterns detected'
                : 'Expense patterns are normal'
        ];
        $totalScore += $expenseAnomalyScore;

        // Factor 3: Late/missing filings (0-30 points)
        $filingComplicanceScore = $this->assessFilingCompliance($user);
        $factors['filing_compliance'] = [
            'score' => $filingComplicanceScore,
            'weight' => 30,
            'status' => $filingComplicanceScore > 20 ? 'high_risk' : 'low_risk',
            'message' => $filingComplicanceScore > 20
                ? 'Late or missing tax filings detected'
                : 'All filings are on time'
        ];
        $totalScore += $filingComplicanceScore;

        // Factor 4: Deduction ratio compared to revenue (0-15 points)
        $deductionRatioScore = $this->assessDeductionRatio($user);
        $factors['deduction_ratio'] = [
            'score' => $deductionRatioScore,
            'weight' => 15,
            'status' => $deductionRatioScore > 10 ? 'medium_risk' : 'low_risk',
            'message' => $deductionRatioScore > 10
                ? 'Deduction ratio is unusually high'
                : 'Deduction ratio is reasonable'
        ];
        $totalScore += $deductionRatioScore;

        // Factor 5: Cash transactions (0-10 points)
        $cashTransactionScore = $this->assessCashTransactions($user);
        $factors['cash_transactions'] = [
            'score' => $cashTransactionScore,
            'weight' => 10,
            'status' => $cashTransactionScore > 5 ? 'medium_risk' : 'low_risk',
            'message' => $cashTransactionScore > 5
                ? 'High proportion of cash transactions'
                : 'Cash transaction level is normal'
        ];
        $totalScore += $cashTransactionScore;

        // Determine overall risk level
        $riskLevel = $this->determineRiskLevel($totalScore);

        return [
            'total_score' => $totalScore,
            'risk_level' => $riskLevel,
            'risk_percentage' => $totalScore,
            'factors' => $factors,
            'recommendations' => $this->generateRecommendations($factors),
            'audit_probability' => $this->estimateAuditProbability($totalScore),
        ];
    }

    protected function assessDocumentationCompleteness(User $user): float
    {
        $score = 0;
        $taxSetting = $user->taxSetting;

        if (!$taxSetting) {
            return 20; // Maximum penalty
        }

        // Missing business name
        if (empty($taxSetting->business_name)) {
            $score += 5;
        }

        // Missing tax ID
        if (empty($taxSetting->tax_id)) {
            $score += 7;
        }

        // Missing fiscal year end
        if (empty($taxSetting->fiscal_year_end)) {
            $score += 5;
        }

        // No tax categories assigned to expenses
        $expensesWithoutCategory = Expense::where('user_id', $user->id)
            ->where('is_tax_deductible', true)
            ->whereNull('tax_category_id')
            ->count();

        if ($expensesWithoutCategory > 0) {
            $score += min(3, $expensesWithoutCategory * 0.5);
        }

        return min(20, $score);
    }

    protected function detectExpenseAnomalies(User $user): float
    {
        $score = 0;
        $yearStart = Carbon::now()->startOfYear();

        // Get monthly expense averages
        $monthlyExpenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $yearStart)
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        if (count($monthlyExpenses) < 2) {
            return 0; // Not enough data
        }

        $average = array_sum($monthlyExpenses) / count($monthlyExpenses);
        $standardDeviation = $this->calculateStandardDeviation($monthlyExpenses, $average);

        // Check for months with unusually high expenses
        foreach ($monthlyExpenses as $amount) {
            if ($standardDeviation > 0 && $amount > $average + (2 * $standardDeviation)) {
                $score += 5; // Large spike detected
            }
        }

        return min(25, $score);
    }

    protected function assessFilingCompliance(User $user): float
    {
        $score = 0;

        // Check for overdue tax periods
        $overduePeriods = TaxPeriod::where('user_id', $user->id)
            ->where('filing_deadline', '<', now())
            ->where('status', '!=', 'filed')
            ->count();

        $score += $overduePeriods * 15; // 15 points per overdue period

        // Check for active periods approaching deadline
        $upcomingDeadlines = TaxPeriod::where('user_id', $user->id)
            ->where('filing_deadline', '>', now())
            ->where('filing_deadline', '<=', now()->addDays(7))
            ->where('status', 'active')
            ->count();

        $score += $upcomingDeadlines * 5; // 5 points per upcoming deadline

        return min(30, $score);
    }

    protected function assessDeductionRatio(User $user): float
    {
        $yearStart = Carbon::now()->startOfYear();
        $taxService = app(TaxCalculationService::class);

        $summary = $taxService->calculateTaxSummary($user, $yearStart, now());

        if ($summary['total_revenue'] == 0) {
            return 0;
        }

        $deductionRatio = ($summary['total_deductions'] / $summary['total_revenue']) * 100;

        // Industry average is typically 15-30%
        // Flag if above 50%
        if ($deductionRatio > 50) {
            return 15;
        } elseif ($deductionRatio > 40) {
            return 10;
        } elseif ($deductionRatio > 35) {
            return 5;
        }

        return 0;
    }

    protected function assessCashTransactions(User $user): float
    {
        // For now, return 0 as we don't track payment methods
        // This can be extended when payment method tracking is added
        return 0;
    }

    protected function calculateStandardDeviation(array $values, float $average): float
    {
        $variance = 0;
        $count = count($values);

        if ($count < 2) {
            return 0;
        }

        foreach ($values as $value) {
            $variance += pow($value - $average, 2);
        }

        return sqrt($variance / $count);
    }

    protected function determineRiskLevel(float $score): string
    {
        if ($score >= 60) {
            return 'high';
        } elseif ($score >= 35) {
            return 'medium';
        } elseif ($score >= 15) {
            return 'low';
        }

        return 'minimal';
    }

    protected function estimateAuditProbability(float $score): string
    {
        $probability = min(100, $score * 1.2);

        if ($probability >= 70) {
            return 'Very High (' . round($probability) . '%)';
        } elseif ($probability >= 50) {
            return 'High (' . round($probability) . '%)';
        } elseif ($probability >= 30) {
            return 'Moderate (' . round($probability) . '%)';
        } elseif ($probability >= 15) {
            return 'Low (' . round($probability) . '%)';
        }

        return 'Very Low (' . round($probability) . '%)';
    }

    protected function generateRecommendations(array $factors): array
    {
        $recommendations = [];

        foreach ($factors as $key => $factor) {
            if ($factor['score'] > ($factor['weight'] * 0.5)) {
                $recommendations[] = match($key) {
                    'documentation_completeness' => 'Complete your tax profile with business name, tax ID, and categorize all expenses',
                    'expense_anomalies' => 'Review unusual expense spikes and ensure proper documentation',
                    'filing_compliance' => 'File overdue tax returns immediately to avoid penalties',
                    'deduction_ratio' => 'Review your deductions - high ratios may trigger audits',
                    'cash_transactions' => 'Maintain detailed records for all cash transactions',
                    default => 'Review this compliance area for improvements'
                };
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your tax compliance is excellent! Keep maintaining good records.';
        }

        return $recommendations;
    }

    /**
     * Get upcoming compliance deadlines
     */
    public function getUpcomingDeadlines(User $user, int $daysAhead = 30): array
    {
        return TaxPeriod::where('user_id', $user->id)
            ->where('status', '!=', 'filed')
            ->where('filing_deadline', '>=', now())
            ->where('filing_deadline', '<=', now()->addDays($daysAhead))
            ->orderBy('filing_deadline')
            ->get()
            ->map(function ($period) {
                return [
                    'id' => $period->id,
                    'name' => $period->name,
                    'deadline' => $period->filing_deadline,
                    'days_remaining' => now()->diffInDays($period->filing_deadline),
                    'urgency' => $this->calculateUrgency($period->filing_deadline),
                ];
            })
            ->toArray();
    }

    protected function calculateUrgency(Carbon $deadline): string
    {
        $daysRemaining = now()->diffInDays($deadline);

        if ($daysRemaining <= 3) {
            return 'critical';
        } elseif ($daysRemaining <= 7) {
            return 'high';
        } elseif ($daysRemaining <= 14) {
            return 'medium';
        }

        return 'low';
    }
}
