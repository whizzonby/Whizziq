<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\FinancialMetricsCalculator;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class BusinessPerformanceTrendWidget extends ChartWidget
{
    protected static ?int $sort = 9;


    protected ?string $heading = '📈 Business Performance Trends';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'monthly';

    protected function getData(): array
    {
        $user = auth()->user();
        $calculator = app(FinancialMetricsCalculator::class);

        return match($this->filter) {
            'quarterly' => $this->getQuarterlyData($calculator, $user),
            'yearly' => $this->getYearlyData($calculator, $user),
            default => $this->getMonthlyData($calculator, $user),
        };
    }

    protected function getMonthlyData($calculator, $user): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $profit = [];
        $cashFlow = [];

        for ($i = 11; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $metrics = $calculator->calculateMetricsForPeriod($user, $startDate, $endDate);

            $labels[] = $startDate->format('M Y');
            $revenue[] = $metrics['revenue'];
            $expenses[] = $metrics['expenses'];
            $profit[] = $metrics['profit'];
            $cashFlow[] = $metrics['cash_flow'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenue,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenses,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Profit',
                    'data' => $profit,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cash Flow',
                    'data' => $cashFlow,
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getQuarterlyData($calculator, $user): array
    {
        $quarterlyData = $calculator->getQuarterlyData($user, 4);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_column($quarterlyData, 'revenue'),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                ],
                [
                    'label' => 'Expenses',
                    'data' => array_column($quarterlyData, 'expenses'),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                ],
                [
                    'label' => 'Profit',
                    'data' => array_column($quarterlyData, 'profit'),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                ],
            ],
            'labels' => array_column($quarterlyData, 'label'),
        ];
    }

    protected function getYearlyData($calculator, $user): array
    {
        $yearlyData = $calculator->getYearlyData($user, 3);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_column($yearlyData, 'revenue'),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                ],
                [
                    'label' => 'Expenses',
                    'data' => array_column($yearlyData, 'expenses'),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                ],
                [
                    'label' => 'Profit',
                    'data' => array_column($yearlyData, 'profit'),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                ],
            ],
            'labels' => array_column($yearlyData, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'monthly' => 'Monthly (Last 12 months)',
            'quarterly' => 'Quarterly (Last 4 quarters)',
            'yearly' => 'Yearly (Last 3 years)',
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
