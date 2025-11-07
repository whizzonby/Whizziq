<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Widgets;

use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueSourceBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue Distribution by Source';

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
                    'label' => 'Revenue by Source',
                    'data' => array_values($data['amounts']),
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // success - subscriptions
                        'rgb(59, 130, 246)',   // info - online sales
                        'rgb(168, 85, 247)',   // primary - consulting
                        'rgb(251, 191, 36)',   // warning - licensing
                        'rgb(236, 72, 153)',   // pink - partnerships
                        'rgb(20, 184, 166)',   // teal
                        'rgb(249, 115, 22)',   // orange
                        'rgb(156, 163, 175)',  // gray
                    ],
                ],
            ],
            'labels' => array_map(fn($src) => ucwords(str_replace('_', ' ', $src)), array_keys($data['amounts'])),
        ];
    }

    protected function getMonthData($user): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $revenues = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($revenues as $revenue) {
            $amounts[$revenue->source] = (float) $revenue->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getWeekData($user): array
    {
        $weekAgo = Carbon::now()->subDays(7);

        $revenues = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $weekAgo)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($revenues as $revenue) {
            $amounts[$revenue->source] = (float) $revenue->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getYearData($user): array
    {
        $startOfYear = Carbon::now()->startOfYear();

        $revenues = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfYear)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $amounts = [];
        foreach ($revenues as $revenue) {
            $amounts[$revenue->source] = (float) $revenue->total;
        }

        return ['amounts' => $amounts];
    }

    protected function getType(): string
    {
        return 'pie';
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
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                label += new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0,
                                }).format(context.parsed);
                                label += " (" + percentage + "%)";
                            }
                            return label;
                        }',
                    ],
                ],
            ],
        ];
    }
}
