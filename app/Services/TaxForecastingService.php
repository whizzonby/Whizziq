<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxForecastingService
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService
    ) {}

    /**
     * Forecast tax liability for the remainder of the year
     */
    public function forecastAnnualTax(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $currentMonth = now()->month;

        // Get historical data for current year
        $ytdData = $this->getYearToDateData($user, $year);

        // Forecast remaining months
        $forecast = $this->forecastRemainingYear($user, $ytdData, $currentMonth);

        return [
            'year' => $year,
            'current_month' => $currentMonth,
            'ytd_actual' => $ytdData,
            'forecasted_annual' => $forecast,
            'variance_analysis' => $this->analyzeVariance($ytdData, $forecast),
            'monthly_breakdown' => $this->getMonthlyBreakdown($user, $year),
        ];
    }

    /**
     * Forecast quarterly tax payment
     */
    public function forecastQuarterlyTax(User $user, int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        $actualData = $this->getQuarterActualData($user, $year, $quarter);
        $forecastedData = $this->forecastQuarterData($user, $actualData, $quarter);

        return [
            'quarter' => $quarter,
            'year' => $year,
            'period' => "Q{$quarter} {$year}",
            'actual_to_date' => $actualData,
            'forecasted_total' => $forecastedData,
            'estimated_payment' => $forecastedData['estimated_tax'],
            'payment_deadline' => $this->getQuarterlyPaymentDeadline($year, $quarter),
        ];
    }

    protected function getYearToDateData(User $user, int $year): array
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = now();

        $summary = $this->taxCalculationService->calculateTaxSummary($user, $startDate, $endDate);

        return [
            'months_elapsed' => $startDate->diffInMonths($endDate) + 1,
            'revenue' => $summary['total_revenue'],
            'expenses' => $summary['total_expenses'],
            'deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
        ];
    }

    protected function forecastRemainingYear(User $user, array $ytdData, int $currentMonth): array
    {
        $monthsRemaining = 12 - $currentMonth;

        if ($monthsRemaining <= 0) {
            return $ytdData; // Year is complete
        }

        // Calculate monthly averages from YTD
        $avgMonthlyRevenue = $ytdData['revenue'] / $ytdData['months_elapsed'];
        $avgMonthlyExpenses = $ytdData['expenses'] / $ytdData['months_elapsed'];
        $avgMonthlyDeductions = $ytdData['deductions'] / $ytdData['months_elapsed'];

        // Apply growth/seasonal factors
        $growthFactor = $this->calculateGrowthFactor($user);
        $seasonalFactor = $this->getSeasonalFactor($currentMonth, $monthsRemaining);

        // Forecast remaining months
        $forecastedRevenue = $avgMonthlyRevenue * $monthsRemaining * $growthFactor * $seasonalFactor;
        $forecastedExpenses = $avgMonthlyExpenses * $monthsRemaining * $growthFactor;
        $forecastedDeductions = $avgMonthlyDeductions * $monthsRemaining * $growthFactor;

        // Calculate annual totals
        $annualRevenue = $ytdData['revenue'] + $forecastedRevenue;
        $annualExpenses = $ytdData['expenses'] + $forecastedExpenses;
        $annualDeductions = $ytdData['deductions'] + $forecastedDeductions;
        $annualTaxableIncome = max(0, $annualRevenue - $annualDeductions);

        $taxSetting = $user->taxSetting;
        $taxRate = $taxSetting->tax_rate ?? 25.00;
        $annualTax = $annualTaxableIncome * ($taxRate / 100);

        return [
            'forecasted_revenue' => round($annualRevenue, 2),
            'forecasted_expenses' => round($annualExpenses, 2),
            'forecasted_deductions' => round($annualDeductions, 2),
            'forecasted_taxable_income' => round($annualTaxableIncome, 2),
            'forecasted_tax' => round($annualTax, 2),
            'confidence_level' => $this->calculateConfidenceLevel($ytdData['months_elapsed']),
            'remaining_months' => $monthsRemaining,
        ];
    }

    protected function calculateGrowthFactor(User $user): float
    {
        // Compare this year's average to last year's average
        $thisYearStart = Carbon::create(now()->year, 1, 1);
        $lastYearStart = Carbon::create(now()->year - 1, 1, 1);
        $lastYearEnd = Carbon::create(now()->year - 1, 12, 31);

        $thisYearRevenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $thisYearStart)
            ->sum('amount');

        $lastYearRevenue = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$lastYearStart, $lastYearEnd])
            ->sum('amount');

        if ($lastYearRevenue == 0) {
            return 1.0; // No historical data, assume no growth
        }

        $monthsThisYear = $thisYearStart->diffInMonths(now()) + 1;
        $avgMonthlyThisYear = $thisYearRevenue / $monthsThisYear;
        $avgMonthlyLastYear = $lastYearRevenue / 12;

        if ($avgMonthlyLastYear == 0) {
            return 1.0;
        }

        $growthRate = ($avgMonthlyThisYear - $avgMonthlyLastYear) / $avgMonthlyLastYear;

        // Cap growth factor between 0.5 and 2.0 for reasonableness
        return max(0.5, min(2.0, 1 + $growthRate));
    }

    protected function getSeasonalFactor(int $currentMonth, int $monthsRemaining): float
    {
        // Simple seasonal adjustment
        // Q4 typically has higher revenue for many businesses
        if ($currentMonth <= 9) { // If we're forecasting into Q4
            return 1.1; // Assume 10% boost
        }

        return 1.0; // No seasonal adjustment
    }

    protected function calculateConfidenceLevel(int $monthsOfData): string
    {
        if ($monthsOfData >= 9) {
            return 'high';
        } elseif ($monthsOfData >= 6) {
            return 'medium';
        } elseif ($monthsOfData >= 3) {
            return 'low';
        }

        return 'very_low';
    }

    protected function getQuarterActualData(User $user, int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $startDate = Carbon::create($year, $startMonth, 1);
        $endDate = Carbon::create($year, $startMonth, 1)->addMonths(3)->subDay();

        // Only use data up to current date if in current quarter
        if ($endDate->isFuture()) {
            $endDate = now();
        }

        $summary = $this->taxCalculationService->calculateTaxSummary($user, $startDate, $endDate);

        return [
            'revenue' => $summary['total_revenue'],
            'expenses' => $summary['total_expenses'],
            'deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
        ];
    }

    protected function forecastQuarterData(User $user, array $actualData, int $quarter): array
    {
        // If quarter is complete, return actual data
        $quarterEndMonth = $quarter * 3;
        if (now()->month > $quarterEndMonth || now()->year > now()->year) {
            return $actualData;
        }

        // Otherwise, forecast remaining days in quarter
        $daysInQuarter = 90; // Approximate
        $daysElapsed = now()->day + ((now()->month - (($quarter - 1) * 3 + 1)) * 30);
        $daysRemaining = $daysInQuarter - $daysElapsed;

        if ($daysRemaining <= 0) {
            return $actualData;
        }

        $dailyAvgRevenue = $actualData['revenue'] / $daysElapsed;
        $dailyAvgDeductions = $actualData['deductions'] / $daysElapsed;

        $forecastedRevenue = $actualData['revenue'] + ($dailyAvgRevenue * $daysRemaining);
        $forecastedDeductions = $actualData['deductions'] + ($dailyAvgDeductions * $daysRemaining);
        $forecastedTaxableIncome = max(0, $forecastedRevenue - $forecastedDeductions);

        $taxSetting = $user->taxSetting;
        $taxRate = $taxSetting->tax_rate ?? 25.00;

        return [
            'revenue' => round($forecastedRevenue, 2),
            'deductions' => round($forecastedDeductions, 2),
            'taxable_income' => round($forecastedTaxableIncome, 2),
            'estimated_tax' => round($forecastedTaxableIncome * ($taxRate / 100), 2),
        ];
    }

    protected function getQuarterlyPaymentDeadline(int $year, int $quarter): string
    {
        $deadlines = [
            1 => Carbon::create($year, 4, 15),
            2 => Carbon::create($year, 6, 15),
            3 => Carbon::create($year, 9, 15),
            4 => Carbon::create($year + 1, 1, 15),
        ];

        return $deadlines[$quarter]->format('Y-m-d');
    }

    protected function analyzeVariance(array $ytdData, array $forecast): array
    {
        $percentComplete = ($ytdData['months_elapsed'] / 12) * 100;
        $revenueRunRate = ($ytdData['revenue'] / $ytdData['months_elapsed']) * 12;

        return [
            'percent_year_complete' => round($percentComplete, 1),
            'revenue_run_rate' => round($revenueRunRate, 2),
            'forecast_vs_run_rate' => round($forecast['forecasted_revenue'] - $revenueRunRate, 2),
            'tax_liability_status' => $this->assessTaxLiabilityStatus($ytdData, $forecast),
        ];
    }

    protected function assessTaxLiabilityStatus(array $ytdData, array $forecast): array
    {
        $expectedYtdTax = $forecast['forecasted_tax'] * ($ytdData['months_elapsed'] / 12);
        $varianceAmount = $ytdData['estimated_tax'] - $expectedYtdTax;

        if ($varianceAmount < 0) {
            return [
                'status' => 'higher_liability',
                'amount' => round(abs($varianceAmount), 2),
                'message' => 'Tax liability trending higher than expected. Consider quarterly payments.',
            ];
        } else {
            return [
                'status' => 'on_track',
                'amount' => round($varianceAmount, 2),
                'message' => 'Tax liability tracking as expected for this period.',
            ];
        }
    }

    protected function getMonthlyBreakdown(User $user, int $year): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            // Skip future months
            if ($startDate->isFuture()) {
                break;
            }

            $revenue = RevenueSource::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $expenses = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $months[] = [
                'month' => $month,
                'month_name' => $startDate->format('F'),
                'revenue' => round($revenue, 2),
                'expenses' => round($expenses, 2),
            ];
        }

        return $months;
    }

    /**
     * Generate forecast summary for dashboard
     */
    public function getDashboardForecast(User $user): array
    {
        $annualForecast = $this->forecastAnnualTax($user);

        $currentQuarter = (int) ceil(now()->month / 3);
        $quarterlyForecast = $this->forecastQuarterlyTax($user, now()->year, $currentQuarter);

        return [
            'annual' => [
                'forecasted_tax' => $annualForecast['forecasted_annual']['forecasted_tax'],
                'confidence' => $annualForecast['forecasted_annual']['confidence_level'],
                'months_remaining' => $annualForecast['forecasted_annual']['remaining_months'],
            ],
            'quarterly' => [
                'quarter' => $currentQuarter,
                'estimated_payment' => $quarterlyForecast['estimated_payment'],
                'deadline' => $quarterlyForecast['payment_deadline'],
            ],
            'variance' => $annualForecast['variance_analysis'],
        ];
    }
}
