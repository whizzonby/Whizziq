<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\BusinessMetric;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialKpiWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $latestMetric = BusinessMetric::where('user_id', $user->id)
            ->latest('date')
            ->first();

        if (!$latestMetric) {
            return [
                Stat::make('Current Cash Flow', '$0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('gray'),
                Stat::make('Current Revenue', '$0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('gray'),
                Stat::make('Current Profit', '$0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('gray'),
                Stat::make('Current Expenses', '$0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('Current Cash Flow', '$' . number_format($latestMetric->cash_flow, 0))
                ->description($this->getChangeDescription($latestMetric->cash_flow_change_percentage))
                ->descriptionIcon($this->getChangeIcon($latestMetric->cash_flow_change_percentage))
                ->chart($this->getCashFlowTrend())
                ->color($this->getChangeColor($latestMetric->cash_flow_change_percentage)),

            Stat::make('Current Revenue', '$' . number_format($latestMetric->revenue, 0))
                ->description($this->getChangeDescription($latestMetric->revenue_change_percentage))
                ->descriptionIcon($this->getChangeIcon($latestMetric->revenue_change_percentage))
                ->chart($this->getRevenueTrend())
                ->color($this->getChangeColor($latestMetric->revenue_change_percentage)),

            Stat::make('Current Profit', '$' . number_format($latestMetric->profit, 0))
                ->description($this->getChangeDescription($latestMetric->profit_change_percentage))
                ->descriptionIcon($this->getChangeIcon($latestMetric->profit_change_percentage))
                ->chart($this->getProfitTrend())
                ->color($this->getChangeColor($latestMetric->profit_change_percentage)),

            Stat::make('Current Expenses', '$' . number_format($latestMetric->expenses, 0))
                ->description($this->getChangeDescription($latestMetric->expenses_change_percentage))
                ->descriptionIcon($this->getChangeIcon($latestMetric->expenses_change_percentage))
                ->chart($this->getExpensesTrend())
                ->color($this->getChangeColor($latestMetric->expenses_change_percentage, true)),
        ];
    }

    protected function getChangeDescription(?float $percentage): string
    {
        if ($percentage === null || $percentage == 0) {
            return 'No change';
        }

        return abs($percentage) . '% ' . ($percentage > 0 ? 'increase' : 'decrease');
    }

    protected function getChangeIcon(?float $percentage): string
    {
        if ($percentage === null || $percentage == 0) {
            return 'heroicon-m-minus';
        }

        return $percentage > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function getChangeColor(?float $percentage, bool $inverse = false): string
    {
        if ($percentage === null || $percentage == 0) {
            return 'gray';
        }

        $isPositive = $percentage > 0;

        if ($inverse) {
            return $isPositive ? 'danger' : 'success';
        }

        return $isPositive ? 'success' : 'danger';
    }

    protected function getCashFlowTrend(): array
    {
        return BusinessMetric::where('user_id', auth()->id())
            ->orderBy('date', 'asc')
            ->take(7)
            ->pluck('cash_flow')
            ->toArray();
    }

    protected function getRevenueTrend(): array
    {
        return BusinessMetric::where('user_id', auth()->id())
            ->orderBy('date', 'asc')
            ->take(7)
            ->pluck('revenue')
            ->toArray();
    }

    protected function getProfitTrend(): array
    {
        return BusinessMetric::where('user_id', auth()->id())
            ->orderBy('date', 'asc')
            ->take(7)
            ->pluck('profit')
            ->toArray();
    }

    protected function getExpensesTrend(): array
    {
        return BusinessMetric::where('user_id', auth()->id())
            ->orderBy('date', 'asc')
            ->take(7)
            ->pluck('expenses')
            ->toArray();
    }
}
