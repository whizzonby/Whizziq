<?php

namespace App\Filament\Dashboard\Resources\ExpenseResource\Widgets;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ExpenseCategoryBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Expense Breakdown by Category';

    protected static ?int $sort = 1;

    public ?string $filter = 'month';

    protected function getData(): array
    {
        $user = auth()->user();

        $data = match($this->filter) {
            'week' => $this->getWeekData($user),
            'year' => $this->getYearData($user),
            default => $this->getMonthData($user),
        };

        return [
            'datasets' => [
                [
                    'label' => 'Expenses by Category',
                    'data' => array_values($data['amounts']),
                    'backgroundColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(249, 115, 22)',
                        'rgb(251, 191, 36)',
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                        'rgb(20, 184, 166)',
                        'rgb(156, 163, 175)',
                    ],
                ],
            ],
            'labels' => array_map(fn($cat) => ucwords(str_replace('_', ' ', $cat)), array_keys($data['amounts'])),
        ];
    }

    protected function getMonthData($user): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($expenses as $expense) {
            $amounts[$expense->category] = (float) $expense->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getWeekData($user): array
    {
        $weekAgo = Carbon::now()->subDays(7);

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $weekAgo)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($expenses as $expense) {
            $amounts[$expense->category] = (float) $expense->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getYearData($user): array
    {
        $startOfYear = Carbon::now()->startOfYear();

        $expenses = Expense::where('user_id', $user->id)
            ->where('date', '>=', $startOfYear)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($expenses as $expense) {
            $amounts[$expense->category] = (float) $expense->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 7 Days',
            'month' => 'This Month',
            'year' => 'This Year',
        ];
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
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0,
                                }).format(context.parsed);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
        ];
    }
}
