<?php

namespace App\Services;

use App\Models\User;
use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\ClientPayment;
use App\Models\ClientInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialMetricsCalculator
{
    /**
     * Calculate current month metrics for a user
     */
    public function getCurrentMonthMetrics(User $user): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return $this->calculateMetricsForPeriod($user, $startOfMonth, $endOfMonth);
    }

    /**
     * Calculate last month metrics for a user
     */
    public function getLastMonthMetrics(User $user): array
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        return $this->calculateMetricsForPeriod($user, $startOfLastMonth, $endOfLastMonth);
    }

    /**
     * Calculate metrics for a specific period
     */
    public function calculateMetricsForPeriod(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // Calculate Revenue
        $revenue = $this->calculateRevenue($user, $startDate, $endDate);

        // Calculate Expenses
        $expenses = $this->calculateExpenses($user, $startDate, $endDate);

        // Calculate Profit
        $profit = $revenue - $expenses;

        // Calculate Cash Flow (simplified: revenue from paid invoices - expenses)
        $cashFlow = $this->calculateCashFlow($user, $startDate, $endDate);

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'profit' => round($profit, 2),
            'cash_flow' => round($cashFlow, 2),
            'profit_margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];
    }

    /**
     * Calculate total revenue for period
     * Optimized: ClientPayment already has user_id, no need for whereHas
     */
    protected function calculateRevenue(User $user, Carbon $startDate, Carbon $endDate): float
    {
        // Revenue from RevenueSource
        $revenueFromSources = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Revenue from Client Payments - optimized: use user_id directly instead of whereHas
        $revenueFromPayments = ClientPayment::where('user_id', $user->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        return (float) ($revenueFromSources + $revenueFromPayments);
    }

    /**
     * Calculate total expenses for period
     */
    protected function calculateExpenses(User $user, Carbon $startDate, Carbon $endDate): float
    {
        return (float) Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calculate cash flow (actual money in - money out)
     * Optimized: ClientPayment already has user_id, no need for whereHas
     */
    protected function calculateCashFlow(User $user, Carbon $startDate, Carbon $endDate): float
    {
        // Cash in: Client payments received - optimized: use user_id directly instead of whereHas
        $cashIn = ClientPayment::where('user_id', $user->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        // Cash out: Expenses
        $cashOut = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return (float) ($cashIn - $cashOut);
    }

    /**
     * Calculate percentage change between two periods
     */
    public function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get monthly trend data (last 12 months)
     */
    public function getMonthlyTrend(User $user, string $metric = 'revenue'): array
    {
        $trend = [];

        for ($i = 11; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $metrics = $this->calculateMetricsForPeriod($user, $startDate, $endDate);
            $trend[] = $metrics[$metric] ?? 0;
        }

        return $trend;
    }

    /**
     * Get quarterly data for charts
     */
    public function getQuarterlyData(User $user, int $quarters = 4): array
    {
        $data = [];

        for ($i = $quarters - 1; $i >= 0; $i--) {
            $quarterStart = Carbon::now()->subQuarters($i)->startOfQuarter();
            $quarterEnd = Carbon::now()->subQuarters($i)->endOfQuarter();

            $metrics = $this->calculateMetricsForPeriod($user, $quarterStart, $quarterEnd);

            $data[] = [
                'label' => 'Q' . $quarterStart->quarter . ' ' . $quarterStart->format('Y'),
                'revenue' => $metrics['revenue'],
                'expenses' => $metrics['expenses'],
                'profit' => $metrics['profit'],
                'cash_flow' => $metrics['cash_flow'],
            ];
        }

        return $data;
    }

    /**
     * Get yearly data for charts
     */
    public function getYearlyData(User $user, int $years = 3): array
    {
        $data = [];

        for ($i = $years - 1; $i >= 0; $i--) {
            $yearStart = Carbon::now()->subYears($i)->startOfYear();
            $yearEnd = Carbon::now()->subYears($i)->endOfYear();

            $metrics = $this->calculateMetricsForPeriod($user, $yearStart, $yearEnd);

            $data[] = [
                'label' => $yearStart->format('Y'),
                'revenue' => $metrics['revenue'],
                'expenses' => $metrics['expenses'],
                'profit' => $metrics['profit'],
                'cash_flow' => $metrics['cash_flow'],
            ];
        }

        return $data;
    }

    /**
     * Get outstanding revenue (unpaid invoices)
     */
    public function getOutstandingRevenue(User $user): float
    {
        return (float) ClientInvoice::where('user_id', $user->id)
            ->whereIn('status', ['sent', 'partial'])
            ->sum(DB::raw('total_amount - COALESCE((SELECT SUM(amount) FROM client_payments WHERE client_invoice_id = client_invoices.id), 0)'));
    }

    /**
     * Get comprehensive metrics with comparisons
     */
    public function getMetricsWithComparisons(User $user): array
    {
        $current = $this->getCurrentMonthMetrics($user);
        $previous = $this->getLastMonthMetrics($user);

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => [
                'revenue_change' => $this->calculatePercentageChange($current['revenue'], $previous['revenue']),
                'expenses_change' => $this->calculatePercentageChange($current['expenses'], $previous['expenses']),
                'profit_change' => $this->calculatePercentageChange($current['profit'], $previous['profit']),
                'cash_flow_change' => $this->calculatePercentageChange($current['cash_flow'], $previous['cash_flow']),
            ],
            'trends' => [
                'revenue' => $this->getMonthlyTrend($user, 'revenue'),
                'expenses' => $this->getMonthlyTrend($user, 'expenses'),
                'profit' => $this->getMonthlyTrend($user, 'profit'),
                'cash_flow' => $this->getMonthlyTrend($user, 'cash_flow'),
            ],
        ];
    }
}
