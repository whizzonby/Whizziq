<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Widgets;

use App\Models\MarketingMetric;
use App\Models\SocialMediaConnection;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingMetricsSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Current month metrics
        $currentMetrics = MarketingMetric::where('user_id', auth()->id())
            ->where('date', '>=', $currentMonth)
            ->selectRaw('
                SUM(ad_spend) as total_ad_spend,
                AVG(roi) as avg_roi,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads,
                AVG(clv_cac_ratio) as avg_clv_cac_ratio
            ')
            ->first();

        // Previous month metrics
        $previousMetrics = MarketingMetric::where('user_id', auth()->id())
            ->whereBetween('date', [$lastMonth, $lastMonthEnd])
            ->selectRaw('
                SUM(ad_spend) as total_ad_spend,
                AVG(roi) as avg_roi,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads
            ')
            ->first();

        // Calculate changes
        $adSpendChange = $this->calculatePercentageChange(
            $currentMetrics->total_ad_spend ?? 0,
            $previousMetrics->total_ad_spend ?? 0
        );

        $roiChange = $this->calculatePercentageChange(
            $currentMetrics->avg_roi ?? 0,
            $previousMetrics->avg_roi ?? 0
        );

        $conversionChange = $this->calculatePercentageChange(
            $currentMetrics->total_conversions ?? 0,
            $previousMetrics->total_conversions ?? 0
        );

        $leadChange = $this->calculatePercentageChange(
            $currentMetrics->total_leads ?? 0,
            $previousMetrics->total_leads ?? 0
        );

        // Get monthly trends for charts (last 12 months)
        $adSpendTrend = MarketingMetric::where('user_id', auth()->id())
            ->where('date', '>=', Carbon::now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(ad_spend) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total')
            ->toArray();

        $conversionTrend = MarketingMetric::where('user_id', auth()->id())
            ->where('date', '>=', Carbon::now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(conversions) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total')
            ->toArray();

        $roiTrend = MarketingMetric::where('user_id', auth()->id())
            ->where('date', '>=', Carbon::now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, AVG(roi) as avg_roi')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('avg_roi')
            ->toArray();

        // Connected platforms count
        $connectedPlatforms = SocialMediaConnection::where('user_id', auth()->id())
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Total Ad Spend', '$' . number_format($currentMetrics->total_ad_spend ?? 0, 0))
                ->description($this->formatChange($adSpendChange) . ' vs last month')
                ->descriptionIcon($adSpendChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($adSpendTrend)
                ->color($this->getAdSpendColor($adSpendChange, $currentMetrics->avg_roi ?? 0)),

            Stat::make('Average ROI', number_format($currentMetrics->avg_roi ?? 0, 1) . '%')
                ->description($this->formatChange($roiChange) . ' vs last month')
                ->descriptionIcon($roiChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($roiTrend)
                ->color($this->getROIColor($currentMetrics->avg_roi ?? 0)),

            Stat::make('Total Conversions', number_format($currentMetrics->total_conversions ?? 0, 0))
                ->description($this->formatChange($conversionChange) . ' vs last month')
                ->descriptionIcon($conversionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($conversionTrend)
                ->color($conversionChange >= 0 ? 'success' : 'danger'),

            Stat::make('Total Leads', number_format($currentMetrics->total_leads ?? 0, 0))
                ->description($this->formatChange($leadChange) . ' vs last month')
                ->descriptionIcon($leadChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($leadChange >= 0 ? 'success' : 'warning'),

            Stat::make('Avg CLV:CAC Ratio',
                $currentMetrics->avg_clv_cac_ratio
                    ? number_format($currentMetrics->avg_clv_cac_ratio, 2) . ':1'
                    : 'N/A'
            )
                ->description($this->getCLVCACHealth($currentMetrics->avg_clv_cac_ratio ?? 0))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($this->getCLVCACColor($currentMetrics->avg_clv_cac_ratio ?? 0)),

            Stat::make('Connected Platforms', $connectedPlatforms)
                ->description('Sync marketing data')
                ->descriptionIcon('heroicon-m-link')
                ->url(route('filament.dashboard.pages.dashboard'))
                ->color('info'),
        ];
    }

    protected function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function formatChange(float $change): string
    {
        $formatted = number_format(abs($change), 1);
        return $change >= 0 ? "+{$formatted}%" : "-{$formatted}%";
    }

    protected function getAdSpendColor(float $change, float $roi): string
    {
        // If ROI is good (>100%), increasing spend is good
        if ($roi > 100) {
            return $change >= 0 ? 'success' : 'warning';
        }

        // If ROI is poor, increasing spend is bad
        return $change >= 0 ? 'danger' : 'success';
    }

    protected function getROIColor(float $roi): string
    {
        return match (true) {
            $roi >= 200 => 'success',
            $roi >= 100 => 'info',
            $roi >= 50 => 'warning',
            default => 'danger',
        };
    }

    protected function getCLVCACColor(float $ratio): string
    {
        return match (true) {
            $ratio >= 3 => 'success',
            $ratio >= 2 => 'info',
            $ratio >= 1 => 'warning',
            default => 'danger',
        };
    }

    protected function getCLVCACHealth(float $ratio): string
    {
        return match (true) {
            $ratio >= 3 => 'Excellent unit economics',
            $ratio >= 2 => 'Good unit economics',
            $ratio >= 1 => 'Needs improvement',
            $ratio > 0 => 'Critical - CAC exceeds CLV',
            default => 'No data',
        };
    }
}
