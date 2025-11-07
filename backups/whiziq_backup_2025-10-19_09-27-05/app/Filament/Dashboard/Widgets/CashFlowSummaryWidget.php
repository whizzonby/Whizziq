<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashFlowSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $currentMonth = Carbon::today()->startOfMonth();
        $previousMonth = Carbon::today()->subMonth()->startOfMonth();

        // Current month data
        $currentInflows = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $currentOutflows = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        // Previous month data for trend
        $previousInflows = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$previousMonth, $currentMonth])
            ->sum('amount');

        $previousOutflows = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$previousMonth, $currentMonth])
            ->sum('amount');

        // Calculate metrics
        $netCashFlow = $currentInflows - $currentOutflows;
        $liquidityRatio = $currentOutflows > 0 ? round(($currentInflows / $currentOutflows), 2) : 0;

        // Calculate trends
        $inflowTrend = $previousInflows > 0
            ? round((($currentInflows - $previousInflows) / $previousInflows) * 100, 1)
            : 0;

        $outflowTrend = $previousOutflows > 0
            ? round((($currentOutflows - $previousOutflows) / $previousOutflows) * 100, 1)
            : 0;

        $netCashFlowTrend = ($previousInflows - $previousOutflows) > 0
            ? round((($netCashFlow - ($previousInflows - $previousOutflows)) / ($previousInflows - $previousOutflows)) * 100, 1)
            : 0;

        // Determine liquidity health
        $liquidityColor = match(true) {
            $liquidityRatio >= 2 => 'success',
            $liquidityRatio >= 1 => 'warning',
            default => 'danger',
        };

        $liquidityDescription = match(true) {
            $liquidityRatio >= 2 => 'Excellent liquidity',
            $liquidityRatio >= 1 => 'Adequate liquidity',
            default => 'Low liquidity - caution advised',
        };

        return [
            Stat::make('Total Inflows', '$' . number_format($currentInflows, 2))
                ->description($inflowTrend >= 0 ? "+{$inflowTrend}% from last month" : "{$inflowTrend}% from last month")
                ->descriptionIcon($inflowTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($inflowTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getInflowsChartData()),

            Stat::make('Total Outflows', '$' . number_format($currentOutflows, 2))
                ->description($outflowTrend <= 0 ? "↓{$outflowTrend}% from last month" : "↑{$outflowTrend}% from last month")
                ->descriptionIcon($outflowTrend <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($outflowTrend <= 0 ? 'success' : 'warning')
                ->chart($this->getOutflowsChartData()),

            Stat::make('Net Cash Flow', '$' . number_format($netCashFlow, 2))
                ->description($netCashFlowTrend >= 0 ? "+{$netCashFlowTrend}% from last month" : "{$netCashFlowTrend}% from last month")
                ->descriptionIcon($netCashFlowTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netCashFlow >= 0 ? 'success' : 'danger'),

            Stat::make('Liquidity Ratio', $liquidityRatio . ':1')
                ->description($liquidityDescription)
                ->descriptionIcon('heroicon-m-beaker')
                ->color($liquidityColor),
        ];
    }

    protected function getInflowsChartData(): array
    {
        $user = auth()->user();
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $amount = RevenueSource::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('amount');
            $data[] = $amount;
        }

        return $data;
    }

    protected function getOutflowsChartData(): array
    {
        $user = auth()->user();
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $amount = Expense::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('amount');
            $data[] = $amount;
        }

        return $data;
    }
}
