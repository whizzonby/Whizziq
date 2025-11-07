<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Top Expenses (Last 30 Days)';

    protected static ?int $sort = 4;


    protected function getData(): array
    {
        $user = auth()->user();
        $last30Days = Carbon::today()->subDays(30);

        // Cache expense breakdown data for 1 hour to improve performance
        $cacheKey = "expense_breakdown_widget_{$user->id}_" . now()->format('Y-m-d-H');
        
        $topExpenses = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($user, $last30Days) {
            // Get expenses from the last 30 days, grouped by category
            return Expense::where('user_id', $user->id)
                ->where('date', '>=', $last30Days)
                ->select('category', DB::raw('SUM(amount) as total'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        });

        if ($topExpenses->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [],
                        'backgroundColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => $topExpenses->pluck('total')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 197, 253, 0.8)',
                        'rgba(96, 165, 250, 0.8)',
                        'rgba(191, 219, 254, 0.8)',
                        'rgba(37, 99, 235, 0.8)',
                    ],
                ],
            ],
            'labels' => $topExpenses->pluck('category')->map(function ($category) {
                return ucwords(str_replace('_', ' ', $category));
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'indexAxis' => 'y',
        ];
    }
}
