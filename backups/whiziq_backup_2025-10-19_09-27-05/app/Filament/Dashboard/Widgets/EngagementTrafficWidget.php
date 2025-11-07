<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EngagementTrafficWidget extends BaseWidget
{
    protected static ?int $sort = 13;

    protected function getStats(): array
    {
        $user = auth()->user();
        $today = Carbon::today();

        // Aggregate all channels for today
        $metrics = MarketingMetric::where('user_id', $user->id)
            ->where('date', $today)
            ->get();

        if ($metrics->isEmpty()) {
            return [
                Stat::make('Total Clicks', '0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                    ->color('gray'),
                Stat::make('Total Reach', '0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-eye')
                    ->color('gray'),
                Stat::make('Total Conversions', '0')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('gray'),
                Stat::make('Avg Engagement Rate', '0%')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color('gray'),
            ];
        }

        $totalClicks = $metrics->sum('clicks');
        $totalReach = $metrics->sum('reach');
        $totalConversions = $metrics->sum('conversions');
        $avgEngagementRate = $metrics->avg('engagement_rate');
        $totalImpressions = $metrics->sum('impressions');

        // Calculate CTR (Click Through Rate)
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

        return [
            Stat::make('Total Clicks', number_format($totalClicks))
                ->description("CTR: {$ctr}%")
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('primary')
                ->chart($this->getClicksTrend()),

            Stat::make('Total Reach', number_format($totalReach))
                ->description(number_format($totalImpressions) . ' impressions')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info')
                ->chart($this->getReachTrend()),

            Stat::make('Total Conversions', number_format($totalConversions))
                ->description('Across all channels')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getConversionsTrend()),

            Stat::make('Avg Engagement Rate', number_format($avgEngagementRate, 2) . '%')
                ->description('Engagement quality')
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning')
                ->chart($this->getEngagementTrend()),
        ];
    }

    protected function getClicksTrend(): array
    {
        $user = auth()->user();
        $data = MarketingMetric::where('user_id', $user->id)
            ->where('date', '>=', Carbon::today()->subDays(6))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->sum('clicks');
            })
            ->values()
            ->toArray();

        return !empty($data) ? $data : [0, 0, 0, 0, 0, 0, 0];
    }

    protected function getReachTrend(): array
    {
        $user = auth()->user();
        $data = MarketingMetric::where('user_id', $user->id)
            ->where('date', '>=', Carbon::today()->subDays(6))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->sum('reach');
            })
            ->values()
            ->toArray();

        return !empty($data) ? $data : [0, 0, 0, 0, 0, 0, 0];
    }

    protected function getConversionsTrend(): array
    {
        $user = auth()->user();
        $data = MarketingMetric::where('user_id', $user->id)
            ->where('date', '>=', Carbon::today()->subDays(6))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->sum('conversions');
            })
            ->values()
            ->toArray();

        return !empty($data) ? $data : [0, 0, 0, 0, 0, 0, 0];
    }

    protected function getEngagementTrend(): array
    {
        $user = auth()->user();
        $data = MarketingMetric::where('user_id', $user->id)
            ->where('date', '>=', Carbon::today()->subDays(6))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->avg('engagement_rate');
            })
            ->values()
            ->toArray();

        return !empty($data) ? $data : [0, 0, 0, 0, 0, 0, 0];
    }
}
