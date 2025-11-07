<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Widgets;

use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueMonthlyTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Revenue Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();
        $labels = [];
        $totalRevenue = [];
        $recurringRevenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $labels[] = $startDate->format('M Y');

            $monthTotal = RevenueSource::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $monthRecurring = RevenueSource::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('source', 'subscriptions')
                ->sum('amount');

            $totalRevenue[] = (float) $monthTotal;
            $recurringRevenue[] = (float) $monthRecurring;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => $totalRevenue,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Recurring Revenue (MRR)',
                    'data' => $recurringRevenue,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
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
