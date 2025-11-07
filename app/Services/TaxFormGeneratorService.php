<?php

namespace App\Services;

use App\Models\User;
use App\Models\TaxSetting;
use App\Models\TaxReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaxFormGeneratorService
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService
    ) {}

    /**
     * Generate tax forms for a user and period
     */
    public function generateTaxForms(User $user, int $year): array
    {
        $taxSetting = $user->taxSetting ?? TaxSetting::firstOrCreate(['user_id' => $user->id]);
        
        if (!$taxSetting->hasCompletedSetup()) {
            throw new \Exception('Tax setup incomplete. Please complete your tax settings first.');
        }

        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        $summary = $this->taxCalculationService->calculateTaxSummary($user, $startDate, $endDate);
        
        $forms = [];
        
        // Generate Schedule C (Sole Proprietor)
        if ($taxSetting->business_type === 'sole_proprietor') {
            $forms['schedule_c'] = $this->generateScheduleC($user, $summary, $year);
        }
        
        // Generate 1099 forms for contractors
        $forms['1099_forms'] = $this->generate1099Forms($user, $year);
        
        // Generate quarterly estimated tax forms
        $forms['estimated_tax'] = $this->generateEstimatedTaxForms($user, $year);
        
        // Create tax report record
        $taxReport = $this->createTaxReport($user, $year, $summary, $forms);
        
        return [
            'forms' => $forms,
            'tax_report' => $taxReport,
            'filing_status' => $this->determineFilingStatus($user, $summary),
            'ready_to_file' => $this->isReadyToFile($user, $summary),
        ];
    }

    /**
     * Generate Schedule C form data
     */
    protected function generateScheduleC(User $user, array $summary, int $year): array
    {
        return [
            'form_name' => 'Schedule C - Profit or Loss from Business',
            'tax_year' => $year,
            'business_name' => $user->taxSetting->business_name ?? $user->name,
            'business_type' => $user->taxSetting->business_type ?? 'sole_proprietor',
            'gross_receipts' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'net_profit' => $summary['taxable_income'],
            'expense_breakdown' => $this->getExpenseBreakdown($user, $year),
            'business_use_of_home' => $this->calculateHomeOfficeDeduction($user, $year),
            'vehicle_expenses' => $this->calculateVehicleExpenses($user, $year),
        ];
    }

    /**
     * Generate 1099 forms for contractors
     */
    protected function generate1099Forms(User $user, int $year): array
    {
        // Get payments to contractors (this would need to be implemented based on your data structure)
        $contractorPayments = $this->getContractorPayments($user, $year);
        
        $forms = [];
        foreach ($contractorPayments as $payment) {
            $forms[] = [
                'form_name' => 'Form 1099-NEC',
                'contractor_name' => $payment['contractor_name'],
                'contractor_tin' => $payment['contractor_tin'],
                'amount_paid' => $payment['amount'],
                'tax_year' => $year,
            ];
        }
        
        return $forms;
    }

    /**
     * Generate estimated tax forms
     */
    protected function generateEstimatedTaxForms(User $user, int $year): array
    {
        $quarters = [];
        
        for ($q = 1; $q <= 4; $q++) {
            $startMonth = ($q - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            
            $startDate = Carbon::create($year, $startMonth, 1);
            $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();
            
            $summary = $this->taxCalculationService->calculateTaxSummary($user, $startDate, $endDate);
            
            $quarters[] = [
                'quarter' => $q,
                'period' => "Q{$q} {$year}",
                'estimated_tax' => $summary['estimated_tax'],
                'due_date' => $this->getQuarterlyDueDate($year, $q),
                'form_name' => 'Form 1040-ES',
            ];
        }
        
        return $quarters;
    }

    /**
     * Get expense breakdown by category
     */
    protected function getExpenseBreakdown(User $user, int $year): array
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);
        
        $expenses = \App\Models\Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('taxCategory')
            ->get();
        
        $breakdown = [];
        foreach ($expenses as $expense) {
            $category = $expense->taxCategory->name ?? 'Other';
            if (!isset($breakdown[$category])) {
                $breakdown[$category] = 0;
            }
            $breakdown[$category] += $expense->amount;
        }
        
        return $breakdown;
    }

    /**
     * Calculate home office deduction
     */
    protected function calculateHomeOfficeDeduction(User $user, int $year): array
    {
        // This would need to be implemented based on your home office tracking
        return [
            'eligible' => false,
            'method' => 'simplified', // or 'actual'
            'square_feet' => 0,
            'deduction_amount' => 0,
        ];
    }

    /**
     * Calculate vehicle expenses
     */
    protected function calculateVehicleExpenses(User $user, int $year): array
    {
        // This would need to be implemented based on your vehicle expense tracking
        return [
            'business_miles' => 0,
            'rate_per_mile' => 0.655, // 2023 rate
            'total_deduction' => 0,
        ];
    }

    /**
     * Get contractor payments
     */
    protected function getContractorPayments(User $user, int $year): array
    {
        // This would need to be implemented based on your contractor payment tracking
        return [];
    }

    /**
     * Create tax report record
     */
    protected function createTaxReport(User $user, int $year, array $summary, array $forms): TaxReport
    {
        $reportName = "Tax Report {$year}";
        $pdfPath = $this->generatePdfReport($user, $year, $summary, $forms);
        
        return TaxReport::create([
            'user_id' => $user->id,
            'report_name' => $reportName,
            'report_date' => now(),
            'period_start' => Carbon::create($year, 1, 1),
            'period_end' => Carbon::create($year, 12, 31),
            'total_revenue' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'total_deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
            'pdf_path' => $pdfPath,
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate PDF report
     */
    protected function generatePdfReport(User $user, int $year, array $summary, array $forms): string
    {
        $filename = "tax_report_{$user->id}_{$year}_" . Str::random(10) . '.pdf';
        $path = "tax_reports/{$filename}";
        
        // This would integrate with a PDF generation library like DomPDF or TCPDF
        // For now, we'll create a placeholder
        Storage::put($path, "Tax Report for {$user->name} - {$year}");
        
        return $path;
    }

    /**
     * Determine filing status
     */
    protected function determineFilingStatus(User $user, array $summary): string
    {
        if ($summary['estimated_tax'] < 1000) {
            return 'not_required';
        }
        
        if ($summary['total_revenue'] < 400) {
            return 'not_required';
        }
        
        return 'required';
    }

    /**
     * Check if ready to file
     */
    protected function isReadyToFile(User $user, array $summary): bool
    {
        $taxSetting = $user->taxSetting;
        
        if (!$taxSetting->hasCompletedSetup()) {
            return false;
        }
        
        if ($summary['estimated_tax'] < 1000) {
            return false;
        }
        
        return true;
    }

    /**
     * Get quarterly due date
     */
    protected function getQuarterlyDueDate(int $year, int $quarter): string
    {
        $dueDates = [
            1 => Carbon::create($year, 4, 15),
            2 => Carbon::create($year, 6, 15),
            3 => Carbon::create($year, 9, 15),
            4 => Carbon::create($year + 1, 1, 15),
        ];
        
        return $dueDates[$quarter]->format('Y-m-d');
    }
}
