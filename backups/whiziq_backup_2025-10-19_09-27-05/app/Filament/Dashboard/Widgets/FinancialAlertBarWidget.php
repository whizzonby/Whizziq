<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class FinancialAlertBarWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.dashboard.widgets.financial-alert-bar-widget';

    protected int | string | array $columnSpan = 'full';

    public function getAlerts(): array
    {
        $user = auth()->user();
        $alerts = [];

        // Check: Expenses growing faster than revenue
        $expenseGrowthAlert = $this->checkExpenseGrowth($user);
        if ($expenseGrowthAlert) {
            $alerts[] = $expenseGrowthAlert;
        }

        // Check: Cash flow declining
        $cashFlowAlert = $this->checkCashFlowTrend($user);
        if ($cashFlowAlert) {
            $alerts[] = $cashFlowAlert;
        }

        // Check: Low liquidity
        $liquidityAlert = $this->checkLiquidity($user);
        if ($liquidityAlert) {
            $alerts[] = $liquidityAlert;
        }

        // Check: Profitability issues
        $profitabilityAlert = $this->checkProfitability($user);
        if ($profitabilityAlert) {
            $alerts[] = $profitabilityAlert;
        }

        // Check: Unusual expense spike
        $expenseSpikeAlert = $this->checkExpenseSpike($user);
        if ($expenseSpikeAlert) {
            $alerts[] = $expenseSpikeAlert;
        }

        // If no alerts, return a positive message
        if (empty($alerts)) {
            return [[
                'type' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'title' => 'Financial Health Good',
                'message' => 'No critical alerts detected. Your financial metrics are within healthy ranges.',
                'action' => null,
            ]];
        }

        return $alerts;
    }

    protected function checkExpenseGrowth($user): ?array
    {
        $currentMonth = Carbon::today()->startOfMonth();
        $twoMonthsAgo = $currentMonth->copy()->subMonths(2);

        $currentRevenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $currentExpenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $pastRevenue = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$twoMonthsAgo, $currentMonth])
            ->sum('amount');

        $pastExpenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$twoMonthsAgo, $currentMonth])
            ->sum('amount');

        if ($pastRevenue > 0 && $pastExpenses > 0) {
            $revenueGrowth = (($currentRevenue - $pastRevenue) / $pastRevenue) * 100;
            $expenseGrowth = (($currentExpenses - $pastExpenses) / $pastExpenses) * 100;

            if ($expenseGrowth > $revenueGrowth && $expenseGrowth > 10) {
                return [
                    'type' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'title' => 'Expense Growth Outpacing Revenue',
                    'message' => sprintf(
                        'Expenses have grown %.1f%% while revenue only grew %.1f%% over the last 2 months.',
                        $expenseGrowth,
                        $revenueGrowth
                    ),
                    'action' => 'Review expense categories',
                ];
            }
        }

        return null;
    }

    protected function checkCashFlowTrend($user): ?array
    {
        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $monthStart = Carbon::today()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $revenue = RevenueSource::where('user_id', $user->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $expenses = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $months[] = $revenue - $expenses;
        }

        // Check if declining for 2 consecutive months
        if (count($months) >= 3 && $months[0] < $months[1] && $months[1] < $months[2]) {
            return [
                'type' => 'danger',
                'icon' => 'heroicon-o-arrow-trending-down',
                'title' => 'Cash Flow Declining',
                'message' => 'Net cash flow has been declining for the past 2 months. Take action to improve revenue or reduce costs.',
                'action' => 'View detailed analysis',
            ];
        }

        return null;
    }

    protected function checkLiquidity($user): ?array
    {
        $currentMonth = Carbon::today()->startOfMonth();

        $revenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        if ($expenses > 0) {
            $liquidityRatio = $revenue / $expenses;

            if ($liquidityRatio < 1) {
                return [
                    'type' => 'danger',
                    'icon' => 'heroicon-o-exclamation-circle',
                    'title' => 'Critical: Low Liquidity',
                    'message' => sprintf(
                        'Your liquidity ratio is %.2f:1. Expenses exceed revenue this month. Immediate attention required.',
                        $liquidityRatio
                    ),
                    'action' => 'Review budget',
                ];
            }
        }

        return null;
    }

    protected function checkProfitability($user): ?array
    {
        $currentMonth = Carbon::today()->startOfMonth();

        $revenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        if ($revenue > 0) {
            $netMargin = (($revenue - $expenses) / $revenue) * 100;

            if ($netMargin < 5) {
                return [
                    'type' => 'warning',
                    'icon' => 'heroicon-o-chart-bar',
                    'title' => 'Low Profitability Margin',
                    'message' => sprintf(
                        'Your net margin is only %.1f%%, which is below the recommended 15%% minimum.',
                        $netMargin
                    ),
                    'action' => 'View profitability ratios',
                ];
            }
        }

        return null;
    }

    protected function checkExpenseSpike($user): ?array
    {
        $today = Carbon::today();
        $lastWeek = $today->copy()->subWeek();

        $recentExpenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $lastWeek)
            ->sum('amount');

        $previousWeek = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$lastWeek->copy()->subWeek(), $lastWeek])
            ->sum('amount');

        if ($previousWeek > 0) {
            $spike = (($recentExpenses - $previousWeek) / $previousWeek) * 100;

            if ($spike > 50) {
                return [
                    'type' => 'info',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'title' => 'Unusual Expense Activity',
                    'message' => sprintf(
                        'Your expenses increased by %.1f%% this week compared to last week.',
                        $spike
                    ),
                    'action' => 'Review recent expenses',
                ];
            }
        }

        return null;
    }
}
