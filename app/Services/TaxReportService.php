<?php

namespace App\Services;

use App\Models\TaxPeriod;
use App\Models\TaxReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TaxReportService
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService
    ) {}

    /**
     * Generate a tax report for a specific period
     */
    public function generateReport(User $user, TaxPeriod $taxPeriod): TaxReport
    {
        // Calculate tax summary for the period
        $summary = $this->taxCalculationService->calculateTaxSummary(
            $user,
            $taxPeriod->start_date,
            $taxPeriod->end_date
        );

        // Create or update tax report
        $report = TaxReport::updateOrCreate(
            [
                'user_id' => $user->id,
                'tax_period_id' => $taxPeriod->id,
            ],
            [
                'report_name' => $taxPeriod->name . ' Tax Report',
                'report_date' => now(),
                'period_start' => $taxPeriod->start_date,
                'period_end' => $taxPeriod->end_date,
                'total_revenue' => $summary['total_revenue'],
                'total_expenses' => $summary['total_expenses'],
                'total_deductions' => $summary['total_deductions'],
                'taxable_income' => $summary['taxable_income'],
                'estimated_tax' => $summary['estimated_tax'],
                'generated_at' => now(),
            ]
        );

        return $report;
    }

    /**
     * Generate year-to-date report
     */
    public function generateYTDReport(User $user, ?int $year = null): TaxReport
    {
        $year = $year ?? now()->year;
        $startDate = Carbon::create($year, 1, 1);
        $endDate = now();

        $summary = $this->taxCalculationService->calculateTaxSummary($user, $startDate, $endDate);

        return TaxReport::create([
            'user_id' => $user->id,
            'report_name' => 'Year-to-Date Tax Report ' . $year,
            'report_date' => now(),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_revenue' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'total_deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate quarterly report
     */
    public function generateQuarterlyReport(User $user, int $year, int $quarter): TaxReport
    {
        $summary = $this->taxCalculationService->getQuarterlySummary($user, $year, $quarter);

        $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
        $endDate = $startDate->copy()->addMonths(3)->subDay();

        return TaxReport::create([
            'user_id' => $user->id,
            'report_name' => "Q{$quarter} {$year} Tax Report",
            'report_date' => now(),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_revenue' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'total_deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate HTML for tax report
     */
    public function generateReportHtml(TaxReport $report): string
    {
        $user = $report->user;
        $taxSetting = $user->taxSetting;

        // Get deductions breakdown
        $deductions = $this->taxCalculationService->getDeductionsByCategory(
            $user,
            Carbon::parse($report->period_start),
            Carbon::parse($report->period_end)
        );

        return view('tax.report-pdf', [
            'report' => $report,
            'user' => $user,
            'taxSetting' => $taxSetting,
            'deductions' => $deductions,
        ])->render();
    }

    /**
     * Save report as PDF (requires PDF library)
     * This is a placeholder for when a PDF library is installed
     */
    public function savePdf(TaxReport $report): ?string
    {
        // Check if DomPDF or similar is available
        if (!class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            // No PDF library installed, return null
            return null;
        }

        try {
            $html = $this->generateReportHtml($report);

            // Generate PDF using DomPDF (when installed)
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

            // Generate filename
            $filename = 'tax-reports/' . $report->user_id . '/' . $report->id . '-' . time() . '.pdf';

            // Save to storage
            Storage::put($filename, $pdf->output());

            // Update report with PDF path
            $report->update(['pdf_path' => $filename]);

            return $filename;
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download report as PDF
     */
    public function downloadPdf(TaxReport $report): ?\Symfony\Component\HttpFoundation\Response
    {
        if (!$report->hasPdf()) {
            // Try to generate PDF
            $this->savePdf($report);
        }

        if (!$report->hasPdf()) {
            return null;
        }

        return Storage::download($report->pdf_path, $report->report_name . '.pdf');
    }

    /**
     * Get all reports for a user
     */
    public function getUserReports(User $user, int $limit = 10)
    {
        return TaxReport::where('user_id', $user->id)
            ->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
