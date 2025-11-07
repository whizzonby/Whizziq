<?php

namespace App\Services;

use App\Models\BusinessMetric;
use App\Models\CashFlowHistory;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessMetricAggregationService
{
    /**
     * Aggregate financial data into BusinessMetric records
     * This powers the main KPI widgets on the dashboard
     *
     * @param int $userId User ID to aggregate for
     * @param Carbon|null $startDate Start date (defaults to 30 days ago)
     * @param Carbon|null $endDate End date (defaults to today)
     * @return array Statistics about the aggregation
     */
    public function aggregateMetrics(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::today()->subDays(30);
        $endDate = $endDate ?? Carbon::today();

        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            // Aggregate day by day
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                try {
                    $result = $this->aggregateDailyMetric($userId, $currentDate);

                    if ($result['created']) {
                        $created++;
                    } elseif ($result['updated']) {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error on {$currentDate->toDateString()}: " . $e->getMessage();
                    Log::error('Daily metric aggregation error', [
                        'user_id' => $userId,
                        'date' => $currentDate->toDateString(),
                        'error' => $e->getMessage(),
                    ]);
                }

                $currentDate->addDay();
            }

            return [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('Metric aggregation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'created' => $created,
                'updated' => $updated,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }
    }

    /**
     * Aggregate metrics for a single day
     */
    protected function aggregateDailyMetric(int $userId, Carbon $date): array
    {
        // Calculate daily totals
        $revenue = $this->getDailyRevenue($userId, $date);
        $expenses = $this->getDailyExpenses($userId, $date);
        $profit = $revenue - $expenses;
        $cashFlow = $this->calculateCashFlow($userId, $date);

        // Get previous day's metric for change calculation
        $previousMetric = BusinessMetric::where('user_id', $userId)
            ->where('date', '<', $date)
            ->orderBy('date', 'desc')
            ->first();

        // Calculate percentage changes
        $revenueChange = $this->calculatePercentageChange(
            $previousMetric?->revenue ?? 0,
            $revenue
        );
        $profitChange = $this->calculatePercentageChange(
            $previousMetric?->profit ?? 0,
            $profit
        );
        $expensesChange = $this->calculatePercentageChange(
            $previousMetric?->expenses ?? 0,
            $expenses
        );
        $cashFlowChange = $this->calculatePercentageChange(
            $previousMetric?->cash_flow ?? 0,
            $cashFlow
        );

        // Create or update record
        $metric = BusinessMetric::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $profit,
                'cash_flow' => $cashFlow,
                'revenue_change_percentage' => $revenueChange,
                'profit_change_percentage' => $profitChange,
                'expenses_change_percentage' => $expensesChange,
                'cash_flow_change_percentage' => $cashFlowChange,
            ]
        );

        return [
            'created' => $metric->wasRecentlyCreated,
            'updated' => !$metric->wasRecentlyCreated,
            'metric' => $metric,
        ];
    }

    /**
     * Get total revenue for a specific date
     * Includes both revenue sources and invoice payments
     */
    protected function getDailyRevenue(int $userId, Carbon $date): float
    {
        $revenueFromSources = RevenueSource::where('user_id', $userId)
            ->whereDate('date', $date)
            ->sum('amount');

        $revenueFromInvoices = ClientPayment::where('user_id', $userId)
            ->whereDate('payment_date', $date)
            ->sum('amount');

        return $revenueFromSources + $revenueFromInvoices;
    }

    /**
     * Get total expenses for a specific date
     */
    protected function getDailyExpenses(int $userId, Carbon $date): float
    {
        return Expense::where('user_id', $userId)
            ->whereDate('date', $date)
            ->sum('amount');
    }

    /**
     * Calculate cumulative cash flow up to a specific date
     * Cash Flow = Total Revenue (all time) + Invoice Payments - Total Expenses (all time)
     */
    protected function calculateCashFlow(int $userId, Carbon $date): float
    {
        $totalRevenue = RevenueSource::where('user_id', $userId)
            ->where('date', '<=', $date)
            ->sum('amount');

        $totalInvoicePayments = ClientPayment::where('user_id', $userId)
            ->where('payment_date', '<=', $date)
            ->sum('amount');

        $totalExpenses = Expense::where('user_id', $userId)
            ->where('date', '<=', $date)
            ->sum('amount');

        return $totalRevenue + $totalInvoicePayments - $totalExpenses;
    }

    /**
     * Calculate percentage change between two values
     */
    protected function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / abs($oldValue)) * 100, 2);
    }

    /**
     * Generate monthly cash flow history records
     * This powers the CashFlowChartWidget
     */
    public function generateCashFlowHistory(int $userId, int $months = 6): array
    {
        $created = 0;
        $updated = 0;

        try {
            for ($i = $months - 1; $i >= 0; $i--) {
                $monthStart = Carbon::today()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::today()->subMonths($i)->endOfMonth();

                // Calculate monthly totals (including invoice payments)
                $monthlyInflow = RevenueSource::where('user_id', $userId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $monthlyInvoicePayments = ClientPayment::where('user_id', $userId)
                    ->whereBetween('payment_date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $monthlyOutflow = Expense::where('user_id', $userId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $netPosition = $monthlyInflow + $monthlyInvoicePayments - $monthlyOutflow;

                // Create or update record
                $record = CashFlowHistory::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'date' => $monthStart,
                    ],
                    [
                        'month_label' => $monthStart->format('M Y'),
                        'amount' => $netPosition,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            return [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Exception $e) {
            Log::error('Cash flow history generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'created' => $created,
                'updated' => $updated,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Aggregate metrics for a specific date range (optimized for imports)
     * This is called after importing data to only update affected dates
     */
    public function aggregateForDateRange(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $metricsResult = $this->aggregateMetrics($userId, $startDate, $endDate);

        // Also regenerate cash flow history if dates span multiple months
        $cashFlowResult = ['success' => true, 'created' => 0, 'updated' => 0];

        if ($startDate->diffInMonths($endDate) > 0 || $startDate->month !== Carbon::today()->month) {
            $cashFlowResult = $this->generateCashFlowHistory($userId);
        }

        return [
            'metrics' => $metricsResult,
            'cash_flow_history' => $cashFlowResult,
        ];
    }

    /**
     * Quick aggregation for today only
     * Useful for real-time dashboard updates
     */
    public function aggregateToday(int $userId): array
    {
        $result = $this->aggregateDailyMetric($userId, Carbon::today());

        return [
            'success' => true,
            'date' => Carbon::today()->toDateString(),
            'metric' => $result['metric'],
        ];
    }

    /**
     * Bulk aggregate for all users (admin function)
     */
    public function aggregateAllUsers(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::today()->subDays(30);
        $endDate = $endDate ?? Carbon::today();

        // Get all users who have financial data (including invoice payments)
        $userIds = DB::table('expenses')
            ->select('user_id')
            ->union(DB::table('revenue_sources')->select('user_id'))
            ->union(DB::table('client_payments')->select('user_id'))
            ->distinct()
            ->pluck('user_id');

        $results = [];

        foreach ($userIds as $userId) {
            $results[$userId] = $this->aggregateMetrics($userId, $startDate, $endDate);
        }

        return [
            'success' => true,
            'users_processed' => count($userIds),
            'results' => $results,
        ];
    }
}
