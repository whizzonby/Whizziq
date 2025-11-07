<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\AIUsageService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AIUsageWidget extends BaseWidget
{
    protected ?string $heading = '🤖 AI Usage & Limits';

    protected static ?int $sort = 2;


    protected function getStats(): array
    {
        $user = auth()->user();
        $usageService = app(AIUsageService::class);

        // Get today's usage
        $todayUsage = $usageService->getTodayUsage($user);

        // Get usage check
        $usageCheck = $usageService->canMakeRequest($user);

        // Get this month's stats
        $monthStats = $usageService->getUsageStats(
            $user,
            Carbon::today()->startOfMonth(),
            Carbon::now()
        );

        $limits = $usageService->getPlanLimits($user);
        $dailyLimit = $limits['daily_limit'];

        // Calculate usage percentage
        $usagePercentage = $dailyLimit > 0 ? ($todayUsage / $dailyLimit) * 100 : 0;

        // Determine color based on usage
        $usageColor = match(true) {
            $usagePercentage >= 90 => 'danger',
            $usagePercentage >= 70 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Today\'s AI Requests', $todayUsage . ' / ' . $dailyLimit)
                ->description($usageCheck['remaining'] . ' requests remaining today')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($usageColor)
                ->chart($this->getTodayChart($usageService, $user))
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('This Month\'s Usage', number_format($monthStats['total_requests']))
                ->description(number_format($monthStats['total_tokens']) . ' tokens used')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info')
                ->chart($this->getMonthChart($usageService, $user)),
        ];
    }

    protected function getTodayChart(AIUsageService $usageService, $user): array
    {
        // Get hourly usage for today
        $stats = \DB::table('ai_usage_logs')
            ->where('user_id', $user->id)
            ->whereDate('requested_at', Carbon::today())
            ->selectRaw('HOUR(requested_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours
        $chart = [];
        for ($i = 0; $i < 24; $i++) {
            $chart[] = $stats[$i] ?? 0;
        }

        return $chart;
    }

    protected function getMonthChart(AIUsageService $usageService, $user): array
    {
        // Get daily usage for this month
        $stats = \DB::table('ai_usage_logs')
            ->where('user_id', $user->id)
            ->whereBetween('requested_at', [
                Carbon::today()->startOfMonth(),
                Carbon::now()
            ])
            ->selectRaw('DATE(requested_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return $stats;
    }

}
