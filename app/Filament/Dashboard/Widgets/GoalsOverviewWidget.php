<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Goal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GoalsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 20;

    protected static bool $isDiscovered = false;


    public function getHeading(): string
    {
        return '🎯 Goals Overview';
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $totalGoals = Goal::where('user_id', $user->id)->active()->count();
        $onTrack = Goal::where('user_id', $user->id)->where('status', 'on_track')->count();
        $atRisk = Goal::where('user_id', $user->id)->whereIn('status', ['at_risk', 'off_track'])->count();
        $completed = Goal::where('user_id', $user->id)->where('status', 'completed')->count();

        return [
            Stat::make('Active Goals', $totalGoals)
                ->description('Currently tracking')
                ->descriptionIcon('heroicon-m-flag')
                ->color('primary'),

            Stat::make('On Track', $onTrack)
                ->description('Making good progress')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Need Attention', $atRisk)
                ->description('At risk or off track')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($atRisk > 0 ? 'danger' : 'gray'),

            Stat::make('Completed', $completed)
                ->description('Goals achieved')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
