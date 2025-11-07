<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueSourcesWidget extends ChartWidget
{
    protected ?string $heading = 'Key Revenue Sources (Last 30 Days)';

    protected static ?int $sort = 3;


    protected function getData(): array
    {
        $user = auth()->user();
        $last30Days = Carbon::today()->subDays(30);

        // Cache revenue sources data for 1 hour to improve performance
        $cacheKey = "revenue_sources_widget_{$user->id}_" . now()->format('Y-m-d-H');
        
        $revenueSources = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($user, $last30Days) {
            // Get revenue sources from the last 30 days, aggregated by source
            return RevenueSource::where('user_id', $user->id)
                ->where('date', '>=', $last30Days)
                ->selectRaw('source, AVG(percentage) as avg_percentage')
                ->groupBy('source')
                ->orderByDesc('avg_percentage')
                ->limit(5)
                ->get();
        });

        if ($revenueSources->isEmpty()) {
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

        $colors = [
            'rgba(59, 130, 246, 0.8)',  // Blue
            'rgba(147, 197, 253, 0.8)',  // Light Blue
            'rgba(96, 165, 250, 0.8)',   // Medium Blue
            'rgba(191, 219, 254, 0.8)',  // Very Light Blue
            'rgba(37, 99, 235, 0.8)',    // Dark Blue
        ];

        return [
            'datasets' => [
                [
                    'data' => $revenueSources->pluck('avg_percentage')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $revenueSources->count()),
                ],
            ],
            'labels' => $revenueSources->pluck('source')->map(function ($source) {
                return ucwords(str_replace('_', ' ', $source));
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
