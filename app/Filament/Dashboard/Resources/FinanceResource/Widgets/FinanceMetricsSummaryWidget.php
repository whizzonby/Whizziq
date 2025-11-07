<?php

namespace App\Filament\Dashboard\Resources\FinanceResource\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\FinancialConnection;
use App\Services\FinancialMetricsCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceMetricsSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $calculator = app(FinancialMetricsCalculator::class);

        // Get current month metrics
        $currentMetrics = $calculator->getCurrentMonthMetrics($user);
        $previousMetrics = $calculator->getLastMonthMetrics($user);

        // Calculate changes
        $revenueChange = $calculator->calculatePercentageChange(
            $currentMetrics['revenue'],
            $previousMetrics['revenue']
        );

        $expenseChange = $calculator->calculatePercentageChange(
            $currentMetrics['expenses'],
            $previousMetrics['expenses']
        );

        // Get revenue trend for sparkline
        $revenueTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dailyRevenue = RevenueSource::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('amount');
            $revenueTrend[] = (float) $dailyRevenue;
        }

        // Get expense trend for sparkline
        $expenseTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dailyExpense = Expense::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('amount');
            $expenseTrend[] = (float) $dailyExpense;
        }

        // Count connected platforms
        $connectedPlatforms = FinancialConnection::where('user_id', $user->id)->count();

        // Count total records imported
        $totalRecords = Expense::where('user_id', $user->id)->count() +
                        RevenueSource::where('user_id', $user->id)->count();

        return [
            Stat::make('Total Revenue', '$' . number_format($currentMetrics['revenue'], 0))
                ->description($this->getChangeDescription($revenueChange, 'revenue'))
                ->descriptionIcon($this->getChangeIcon($revenueChange))
                ->chart($revenueTrend)
                ->color($this->getRevenueColor($revenueChange)),

            Stat::make('Total Expenses', '$' . number_format($currentMetrics['expenses'], 0))
                ->description($this->getChangeDescription($expenseChange, 'expense'))
                ->descriptionIcon($this->getChangeIcon($expenseChange))
                ->chart($expenseTrend)
                ->color($this->getExpenseColor($expenseChange)),

            Stat::make('Profit Margin', number_format($currentMetrics['profit_margin'], 1) . '%')
                ->description('Current month')
                ->descriptionIcon('heroicon-o-chart-pie')
                ->color($this->getMarginColor($currentMetrics['profit_margin'])),

            Stat::make('Connected Platforms', $connectedPlatforms)
                ->description($totalRecords . ' records imported')
                ->descriptionIcon('heroicon-o-cloud-arrow-down')
                ->color($connectedPlatforms > 0 ? 'success' : 'gray')
                ->url(route('filament.dashboard.pages.dashboard'))
                ->extraAttributes(['title' => 'View AI Dashboard']),
        ];
    }

    protected function getChangeDescription(float $percentage, string $type): string
    {
        if ($percentage == 0) {
            return 'No change from last month';
        }

        $direction = $percentage > 0 ? 'increase' : 'decrease';
        return abs($percentage) . '% ' . $direction . ' vs last month';
    }

    protected function getChangeIcon(float $percentage): string
    {
        if ($percentage == 0) {
            return 'heroicon-m-minus';
        }

        return $percentage > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function getRevenueColor(float $percentage): string
    {
        if ($percentage == 0) {
            return 'gray';
        }

        // For revenue, increase is good (green), decrease is bad (red)
        return $percentage > 0 ? 'success' : 'danger';
    }

    protected function getExpenseColor(float $percentage): string
    {
        if ($percentage == 0) {
            return 'gray';
        }

        // For expenses, decrease is good (green), increase is bad (red)
        return $percentage < 0 ? 'success' : 'warning';
    }

    protected function getMarginColor(float $margin): string
    {
        if ($margin >= 20) {
            return 'success';
        } elseif ($margin >= 10) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}
