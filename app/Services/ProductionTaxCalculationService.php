<?php

namespace App\Services;

use App\Models\{User, Expense, RevenueSource, ClientPayment, TaxSetting};
use Carbon\Carbon;

class ProductionTaxCalculationService
{
    /**
     * 2024 Federal Tax Brackets (Single)
     */
    protected array $federalBracketsSingle2024 = [
        ['min' => 0, 'max' => 11600, 'rate' => 10],
        ['min' => 11600, 'max' => 47150, 'rate' => 12],
        ['min' => 47150, 'max' => 100525, 'rate' => 22],
        ['min' => 100525, 'max' => 191950, 'rate' => 24],
        ['min' => 191950, 'max' => 243725, 'rate' => 32],
        ['min' => 243725, 'max' => 609350, 'rate' => 35],
        ['min' => 609350, 'max' => PHP_FLOAT_MAX, 'rate' => 37],
    ];

    /**
     * 2024 Federal Tax Brackets (Married Filing Jointly)
     */
    protected array $federalBracketsMarriedJoint2024 = [
        ['min' => 0, 'max' => 23200, 'rate' => 10],
        ['min' => 23200, 'max' => 94300, 'rate' => 12],
        ['min' => 94300, 'max' => 201050, 'rate' => 22],
        ['min' => 201050, 'max' => 383900, 'rate' => 24],
        ['min' => 383900, 'max' => 487450, 'rate' => 32],
        ['min' => 487450, 'max' => 731200, 'rate' => 35],
        ['min' => 731200, 'max' => PHP_FLOAT_MAX, 'rate' => 37],
    ];

    /**
     * 2024 Standard Deductions
     */
    protected array $standardDeductions2024 = [
        'single' => 14600,
        'married_joint' => 29200,
        'married_separate' => 14600,
        'head_of_household' => 21900,
        'qualifying_widow' => 29200,
    ];

    /**
     * Calculate comprehensive tax summary
     */
    public function calculateComprehensiveTaxSummary(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $taxSettings = $user->taxSetting ?? TaxSetting::firstOrCreate(['user_id' => $user->id]);

        // Calculate income
        $income = $this->calculateIncome($user, $startDate, $endDate);

        // Calculate business expenses
        $businessExpenses = $this->calculateBusinessExpenses($user, $startDate, $endDate);

        // Calculate business profit
        $businessProfit = max(0, $income['total_revenue'] - $businessExpenses['total_deductible']);

        // Calculate self-employment tax
        $selfEmploymentTax = $this->calculateSelfEmploymentTax($businessProfit);

        // Calculate AGI (Adjusted Gross Income)
        $agi = $businessProfit - ($selfEmploymentTax['deductible_portion']);

        // Apply standard deduction
        $standardDeduction = $this->getStandardDeduction($taxSettings->filing_status ?? 'single');
        $taxableIncome = max(0, $agi - $standardDeduction);

        // Calculate federal income tax using progressive brackets
        $federalIncomeTax = $this->calculateFederalIncomeTax(
            $taxableIncome,
            $taxSettings->filing_status ?? 'single'
        );

        // Calculate state income tax
        $stateIncomeTax = $this->calculateStateIncomeTax(
            $taxableIncome,
            $taxSettings->state ?? 'CA'
        );

        // Total tax liability
        $totalTax = $federalIncomeTax + $selfEmploymentTax['total_tax'] + $stateIncomeTax;

        return [
            // Income
            'total_revenue' => round($income['total_revenue'], 2),
            'w2_income' => round($income['w2_income'], 2),
            'other_income' => round($income['other_income'], 2),

            // Business Calculations
            'total_business_expenses' => round($businessExpenses['total_expenses'], 2),
            'total_deductible_expenses' => round($businessExpenses['total_deductible'], 2),
            'business_profit' => round($businessProfit, 2),

            // Self-Employment Tax
            'self_employment_income' => round($businessProfit, 2),
            'self_employment_tax' => round($selfEmploymentTax['total_tax'], 2),
            'se_tax_deductible' => round($selfEmploymentTax['deductible_portion'], 2),

            // AGI and Taxable Income
            'adjusted_gross_income' => round($agi, 2),
            'standard_deduction' => round($standardDeduction, 2),
            'taxable_income' => round($taxableIncome, 2),

            // Tax Calculations
            'federal_income_tax' => round($federalIncomeTax, 2),
            'state_income_tax' => round($stateIncomeTax, 2),
            'total_tax_liability' => round($totalTax, 2),

            // Effective Rates
            'effective_tax_rate' => $income['total_revenue'] > 0
                ? round(($totalTax / $income['total_revenue']) * 100, 2)
                : 0,
            'marginal_tax_rate' => $this->getMarginalTaxRate(
                $taxableIncome,
                $taxSettings->filing_status ?? 'single'
            ),

            // Expense Breakdown
            'expense_categories' => $businessExpenses['by_category'],

            // Period
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];
    }

    /**
     * Calculate total income from all sources
     */
    protected function calculateIncome(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // Business revenue
        $businessRevenue = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $clientPayments = ClientPayment::whereHas('client', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $totalRevenue = (float) ($businessRevenue + $clientPayments);

        return [
            'total_revenue' => $totalRevenue,
            'business_revenue' => (float) $businessRevenue,
            'client_payments' => (float) $clientPayments,
            'w2_income' => 0, // Would come from W-2 documents
            'other_income' => 0, // Would come from other tax documents
        ];
    }

    /**
     * Calculate business expenses with categorization
     */
    protected function calculateBusinessExpenses(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('taxCategory')
            ->get();

        $totalExpenses = 0;
        $totalDeductible = 0;
        $byCategory = [];

        foreach ($expenses as $expense) {
            $totalExpenses += $expense->amount;

            if ($expense->is_tax_deductible) {
                $deductibleAmount = $expense->calculateDeductibleAmount();
                $totalDeductible += $deductibleAmount;

                $categoryName = $expense->taxCategory->name ?? 'Uncategorized';

                if (!isset($byCategory[$categoryName])) {
                    $byCategory[$categoryName] = [
                        'total' => 0,
                        'deductible' => 0,
                        'count' => 0,
                    ];
                }

                $byCategory[$categoryName]['total'] += $expense->amount;
                $byCategory[$categoryName]['deductible'] += $deductibleAmount;
                $byCategory[$categoryName]['count']++;
            }
        }

        return [
            'total_expenses' => $totalExpenses,
            'total_deductible' => $totalDeductible,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Calculate self-employment tax (Social Security + Medicare)
     */
    protected function calculateSelfEmploymentTax(float $netEarnings): array
    {
        // 2024 values
        $ssTaxRate = 0.124; // 12.4% Social Security
        $medicareTaxRate = 0.029; // 2.9% Medicare
        $additionalMedicareTaxRate = 0.009; // 0.9% Additional Medicare (over $200k)
        $ssWageBase = 168600; // 2024 SS wage base
        $additionalMedicareThreshold = 200000;

        // Calculate 92.35% of net earnings (IRS rule)
        $seIncome = $netEarnings * 0.9235;

        // Social Security tax (capped at wage base)
        $ssTax = min($seIncome, $ssWageBase) * $ssTaxRate;

        // Medicare tax (no cap)
        $medicareTax = $seIncome * $medicareTaxRate;

        // Additional Medicare tax (over threshold)
        $additionalMedicareTax = 0;
        if ($seIncome > $additionalMedicareThreshold) {
            $additionalMedicareTax = ($seIncome - $additionalMedicareThreshold) * $additionalMedicareTaxRate;
        }

        $totalTax = $ssTax + $medicareTax + $additionalMedicareTax;

        return [
            'se_income' => $seIncome,
            'ss_tax' => $ssTax,
            'medicare_tax' => $medicareTax,
            'additional_medicare_tax' => $additionalMedicareTax,
            'total_tax' => $totalTax,
            'deductible_portion' => $totalTax / 2, // 50% of SE tax is deductible
        ];
    }

    /**
     * Calculate federal income tax using progressive tax brackets
     */
    protected function calculateFederalIncomeTax(float $taxableIncome, string $filingStatus): float
    {
        $brackets = $this->getTaxBrackets($filingStatus);
        $tax = 0;

        foreach ($brackets as $bracket) {
            if ($taxableIncome <= $bracket['min']) {
                break;
            }

            $taxableInBracket = min($taxableIncome, $bracket['max']) - $bracket['min'];
            $tax += $taxableInBracket * ($bracket['rate'] / 100);
        }

        return $tax;
    }

    /**
     * Calculate state income tax
     */
    protected function calculateStateIncomeTax(float $taxableIncome, string $state): float
    {
        // State tax rates (simplified - actual rates are progressive by state)
        $stateTaxRates = [
            'CA' => 9.3, // California (approximate)
            'NY' => 6.85, // New York
            'TX' => 0, // Texas (no income tax)
            'FL' => 0, // Florida (no income tax)
            'WA' => 0, // Washington (no income tax)
            'IL' => 4.95, // Illinois
            'PA' => 3.07, // Pennsylvania
            'OH' => 3.75, // Ohio
            'GA' => 5.75, // Georgia
            'NC' => 4.75, // North Carolina
            'MI' => 4.25, // Michigan
        ];

        $rate = $stateTaxRates[$state] ?? 5.0; // Default 5% for unlisted states

        return $taxableIncome * ($rate / 100);
    }

    /**
     * Get tax brackets based on filing status
     */
    protected function getTaxBrackets(string $filingStatus): array
    {
        return match($filingStatus) {
            'married_joint', 'qualifying_widow' => $this->federalBracketsMarriedJoint2024,
            default => $this->federalBracketsSingle2024,
        };
    }

    /**
     * Get standard deduction
     */
    protected function getStandardDeduction(string $filingStatus): float
    {
        return $this->standardDeductions2024[$filingStatus] ?? $this->standardDeductions2024['single'];
    }

    /**
     * Get marginal tax rate (top bracket rate)
     */
    protected function getMarginalTaxRate(float $taxableIncome, string $filingStatus): float
    {
        $brackets = $this->getTaxBrackets($filingStatus);

        foreach ($brackets as $bracket) {
            if ($taxableIncome >= $bracket['min'] && $taxableIncome < $bracket['max']) {
                return $bracket['rate'];
            }
        }

        return end($brackets)['rate']; // Return top bracket if income exceeds all
    }

    /**
     * Calculate quarterly estimated tax payment
     */
    public function calculateQuarterlyEstimatedTax(User $user, int $year, int $quarter): array
    {
        $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
        $endDate = $startDate->copy()->addMonths(3)->subDay();

        $quarterSummary = $this->calculateComprehensiveTaxSummary($user, $startDate, $endDate);

        // Estimated tax is total liability divided by 4 quarters
        $estimatedQuarterlyPayment = $quarterSummary['total_tax_liability'];

        return [
            'quarter' => $quarter,
            'year' => $year,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'estimated_payment' => round($estimatedQuarterlyPayment, 2),
            'due_date' => $this->getQuarterlyDueDate($year, $quarter),
            'quarterly_income' => $quarterSummary['total_revenue'],
            'quarterly_expenses' => $quarterSummary['total_deductible_expenses'],
        ];
    }

    /**
     * Get quarterly due dates
     */
    protected function getQuarterlyDueDate(int $year, int $quarter): string
    {
        $dueDates = [
            1 => Carbon::create($year, 4, 15),
            2 => Carbon::create($year, 6, 15),
            3 => Carbon::create($year, 9, 15),
            4 => Carbon::create($year + 1, 1, 15),
        ];

        // Adjust for weekends
        $dueDate = $dueDates[$quarter];
        if ($dueDate->isWeekend()) {
            $dueDate = $dueDate->nextWeekday();
        }

        return $dueDate->toDateString();
    }

    /**
     * Get year-to-date summary
     */
    public function getYearToDateSummary(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $startDate = Carbon::create($year, 1, 1);
        $endDate = now()->endOfDay();

        return $this->calculateComprehensiveTaxSummary($user, $startDate, $endDate);
    }
}
