<?php

namespace App\Filament\Dashboard\Resources\ExpenseResource\Widgets;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ExpenseMonthlyTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Expense Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();
        $labels = [];
        $totalExpenses = [];
        $taxDeductible = [];

        for ($i = 11; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $labels[] = $startDate->format('M Y');

            $monthTotal = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $monthTaxDeductible = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('is_tax_deductible', true)
                ->sum('amount');

            $totalExpenses[] = (float) $monthTotal;
            $taxDeductible[] = (float) $monthTaxDeductible;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Expenses',
                    'data' => $totalExpenses,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Tax Deductible',
                    'data' => $taxDeductible,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0,
                                }).format(context.parsed.y);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("en-US", {
                                style: "currency",
                                currency: "USD",
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                                notation: "compact",
                                compactDisplay: "short",
                            }).format(value);
                        }',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
