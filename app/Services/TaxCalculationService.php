<?php

namespace App\Services;

use App\Models\{User, Expense, RevenueSource, ClientPayment, TaxSetting, TaxCategory};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxCalculationService
{
    public function __construct(
        protected TaxBracketService $taxBracketService
    ) {}
    /**
     * Calculate tax summary for a user and period
     */
    public function calculateTaxSummary(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $taxSettings = $user->taxSetting ?? TaxSetting::firstOrCreate(['user_id' => $user->id]);

        // Calculate Total Revenue
        $totalRevenue = $this->calculateTotalRevenue($user, $startDate, $endDate);

        // Calculate Total Expenses
        $totalExpenses = $this->calculateTotalExpenses($user, $startDate, $endDate);

        // Calculate Tax Deductions
        $totalDeductions = $this->calculateTotalDeductions($user, $startDate, $endDate);

        // Calculate Taxable Income
        $taxableIncome = max(0, $totalRevenue - $totalDeductions);

        // Calculate Tax Using Progressive Brackets
        $taxCalculation = $this->taxBracketService->calculateTotalTax($taxableIncome, $taxSettings);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_deductions' => round($totalDeductions, 2),
            'taxable_income' => round($taxableIncome, 2),
            'federal_tax' => $taxCalculation['federal_tax'],
            'state_tax' => $taxCalculation['state_tax'],
            'total_tax' => $taxCalculation['total_tax'],
            'estimated_tax' => $taxCalculation['total_tax'], // Backwards compatibility
            'effective_tax_rate' => $taxCalculation['overall_effective_rate'],
            'federal_effective_rate' => $taxCalculation['federal_effective_rate'],
            'state_effective_rate' => $taxCalculation['state_effective_rate'],
            'marginal_tax_rate' => $this->taxBracketService->getMarginalTaxRate($taxableIncome, $taxSettings->filing_status ?? 'single'),
            'tax_breakdown' => $taxCalculation['federal_breakdown'] ?? [],
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];
    }

    /**
     * Calculate total revenue for period
     */
    public function calculateTotalRevenue(User $user, Carbon $startDate, Carbon $endDate): float
    {
        // Revenue from RevenueSource (validate non-negative)
        $revenueFromSources = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('amount', '>=', 0) // Only positive amounts
            ->sum('amount');

        // Revenue from Client Payments (validate non-negative)
        $revenueFromPayments = ClientPayment::whereHas('client', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('amount', '>=', 0) // Only positive amounts
            ->sum('amount');

        return max(0, (float) ($revenueFromSources + $revenueFromPayments));
    }

    /**
     * Calculate total expenses for period
     */
    public function calculateTotalExpenses(User $user, Carbon $startDate, Carbon $endDate): float
    {
        $total = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('amount', '>=', 0) // Only positive amounts
            ->sum('amount');

        return max(0, (float) $total);
    }

    /**
     * Calculate tax-deductible expenses
     */
    public function calculateTotalDeductions(User $user, Carbon $startDate, Carbon $endDate): float
    {
        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_tax_deductible', true)
            ->with('taxCategory')
            ->get();

        $totalDeductions = 0;

        foreach ($expenses as $expense) {
            $totalDeductions += $expense->calculateDeductibleAmount();
        }

        return (float) $totalDeductions;
    }


    /**
     * Get deductions by category
     */
    public function getDeductionsByCategory(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_tax_deductible', true)
            ->with('taxCategory')
            ->get();

        $categories = [];
        
        foreach ($expenses as $expense) {
            $categoryName = $expense->taxCategory->name ?? 'Uncategorized';
            
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = [
                    'category_name' => $categoryName,
                    'deductible_amount' => 0,
                    'expense_count' => 0,
                ];
            }
            
            $categories[$categoryName]['deductible_amount'] += $expense->calculateDeductibleAmount();
            $categories[$categoryName]['expense_count']++;
        }
        
        return array_values($categories);
    }

    /**
     * Estimate tax owed based on taxable income
     */
    protected function estimateTaxOwed(float $taxableIncome, float $taxRate): float
    {
        return $taxableIncome * ($taxRate / 100);
    }

    /**
     * Get year-to-date summary
     */
    public function getYearToDateSummary(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $startDate = Carbon::create($year, 1, 1);
        $endDate = now()->endOfDay();

        return $this->calculateTaxSummary($user, $startDate, $endDate);
    }

    /**
     * Get quarterly summary
     */
    public function getQuarterlySummary(User $user, int $year, int $quarter): array
    {
        $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        return $this->calculateTaxSummary($user, $startDate, $endDate);
    }

    /**
     * Auto-categorize expense based on description/category
     */
    public function suggestTaxCategory(Expense $expense): ?TaxCategory
    {
        $description = strtolower($expense->description ?? '');
        $category = strtolower($expense->category ?? '');

        // Simple keyword matching for tax categories
        $keywords = [
            'advertising' => ['ad', 'advertising', 'marketing', 'promotion', 'facebook ads', 'google ads'],
            'office_supplies' => ['office', 'supplies', 'pen', 'paper', 'desk'],
            'software' => ['software', 'subscription', 'saas', 'hosting', 'domain'],
            'meals' => ['meal', 'restaurant', 'lunch', 'dinner', 'coffee'],
            'travel' => ['travel', 'flight', 'hotel', 'airbnb', 'uber', 'lyft'],
            'vehicle' => ['vehicle', 'car', 'gas', 'fuel', 'parking'],
            'rent_utilities' => ['rent', 'utility', 'utilities', 'electricity', 'water', 'internet'],
            'professional_services' => ['lawyer', 'accountant', 'consultant', 'legal', 'accounting'],
            'insurance' => ['insurance'],
            'salaries' => ['salary', 'wage', 'payroll'],
            'contract_labor' => ['contractor', 'freelancer', 'consultant'],
        ];

        foreach ($keywords as $slug => $words) {
            foreach ($words as $word) {
                if (str_contains($description, $word) || str_contains($category, $word)) {
                    return TaxCategory::where('slug', $slug)->first();
                }
            }
        }

        return null;
    }
}
