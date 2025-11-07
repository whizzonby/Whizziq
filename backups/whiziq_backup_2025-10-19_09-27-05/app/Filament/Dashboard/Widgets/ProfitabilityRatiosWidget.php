<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class ProfitabilityRatiosWidget extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.dashboard.widgets.profitability-ratios-widget';

    protected int | string | array $columnSpan = 'full';

    public function getRatios(): array
    {
        $user = auth()->user();
        $currentMonth = Carbon::today()->startOfMonth();

        // Get current month data
        $revenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->sum('amount');

        // Get operating expenses (non-COGS expenses)
        $operatingExpenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $currentMonth)
            ->whereNotIn('category', ['cost_of_goods_sold', 'cogs'])
            ->sum('amount');

        // Calculate metrics
        $netProfit = $revenue - $expenses;
        $operatingProfit = $revenue - $operatingExpenses;

        // Calculate ratios
        $netMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0;
        $operatingMargin = $revenue > 0 ? round(($operatingProfit / $revenue) * 100, 2) : 0;
        $expenseRatio = $revenue > 0 ? round(($expenses / $revenue) * 100, 2) : 0;

        // Determine health status
        $netMarginStatus = $this->getHealthStatus($netMargin, 15, 10, 5);
        $operatingMarginStatus = $this->getHealthStatus($operatingMargin, 20, 15, 10);
        $expenseRatioStatus = $this->getHealthStatus(100 - $expenseRatio, 50, 30, 20);

        return [
            [
                'label' => 'Net Margin',
                'value' => $netMargin . '%',
                'description' => 'Net Profit / Total Revenue',
                'status' => $netMarginStatus,
                'benchmark' => 'Target: >15%',
                'icon' => 'heroicon-o-chart-pie',
            ],
            [
                'label' => 'Operating Margin',
                'value' => $operatingMargin . '%',
                'description' => 'Operating Profit / Total Revenue',
                'status' => $operatingMarginStatus,
                'benchmark' => 'Target: >20%',
                'icon' => 'heroicon-o-calculator',
            ],
            [
                'label' => 'Expense Ratio',
                'value' => $expenseRatio . '%',
                'description' => 'Total Expenses / Total Revenue',
                'status' => $expenseRatioStatus,
                'benchmark' => 'Target: <50%',
                'icon' => 'heroicon-o-banknotes',
            ],
        ];
    }

    protected function getHealthStatus(float $value, float $excellent, float $good, float $fair): array
    {
        if ($value >= $excellent) {
            return [
                'color' => 'success',
                'label' => 'Excellent',
                'icon' => 'heroicon-m-check-circle',
            ];
        } elseif ($value >= $good) {
            return [
                'color' => 'primary',
                'label' => 'Good',
                'icon' => 'heroicon-m-check-badge',
            ];
        } elseif ($value >= $fair) {
            return [
                'color' => 'warning',
                'label' => 'Fair',
                'icon' => 'heroicon-m-exclamation-triangle',
            ];
        } else {
            return [
                'color' => 'danger',
                'label' => 'Needs Improvement',
                'icon' => 'heroicon-m-x-circle',
            ];
        }
    }
}
