<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueInsightsWidget extends BaseWidget
{
    protected static ?int $sort = 4;


    public function getHeading(): string
    {
        return '📈 Revenue Insights';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Current month revenue
        $currentMonthRevenue = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->sum('amount');

        // Last month revenue
        $lastMonthRevenue = RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        // Calculate change
        $revenueChange = $lastMonthRevenue > 0
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        // Top revenue source
        $topSource = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->first();

        // Revenue diversification (count of unique sources)
        $uniqueSources = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->distinct('source')
            ->count('source');

        // MRR (Monthly Recurring Revenue) from subscriptions
        $mrr = RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->where('source', 'subscriptions')
            ->sum('amount');

        // Get revenue trend for last 7 days
        $revenueTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dailyRevenue = RevenueSource::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('amount');
            $revenueTrend[] = (float) $dailyRevenue;
        }

        return [
            Stat::make('Monthly Revenue', '$' . number_format($currentMonthRevenue, 0))
                ->description($this->getChangeDescription($revenueChange))
                ->descriptionIcon($this->getChangeIcon($revenueChange))
                ->chart($revenueTrend)
                ->color($this->getRevenueColor($revenueChange)),

            Stat::make('Top Source', $topSource ? ucwords(str_replace('_', ' ', $topSource->source)) : 'N/A')
                ->description($topSource ? '$' . number_format($topSource->total, 0) . ' (' . number_format(($topSource->total / max($currentMonthRevenue, 1)) * 100, 0) . '%)' : 'No revenue yet')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make($mrr > 0 ? 'MRR' : 'Revenue Streams', $mrr > 0 ? '$' . number_format($mrr, 0) : $uniqueSources)
                ->description($mrr > 0 ? 'Monthly Recurring Revenue' : ($uniqueSources > 1 ? 'Diversified sources' : 'Add more sources'))
                ->descriptionIcon($mrr > 0 ? 'heroicon-o-arrow-path' : 'heroicon-o-squares-2x2')
                ->color($mrr > 0 ? 'success' : ($uniqueSources > 2 ? 'success' : 'warning')),
        ];
    }

    protected function getChangeDescription(float $percentage): string
    {
        if ($percentage == 0) {
            return 'No change from last month';
        }

        return abs($percentage) . '% ' . ($percentage > 0 ? 'growth' : 'decline') . ' vs last month';
    }

    protected function getChangeIcon(float $percentage): string
    {
        if ($percentage == 0) {
            return 'heroicon-m-minus';
        }

        return $percentage > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function getRevenueColor(float $percentage): string
    {
        if ($percentage == 0) {
            return 'gray';
        }

        // For revenue, increase is good (green), decrease is bad (red)
        return $percentage > 0 ? 'success' : 'danger';
    }
}
