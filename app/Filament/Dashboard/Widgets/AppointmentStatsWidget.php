<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppointmentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 25;


    public function getHeading(): string
    {
        return '📅 Appointment Statistics';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        // Get date ranges
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // This month stats
        $thisMonthTotal = Appointment::where('user_id', $userId)
            ->where('start_datetime', '>=', $thisMonth)
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();

        // Last month for comparison
        $lastMonthTotal = Appointment::where('user_id', $userId)
            ->whereBetween('start_datetime', [$lastMonth, $thisMonth])
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();

        $change = $lastMonthTotal > 0
            ? round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : 0;

        // Upcoming appointments
        $upcoming = Appointment::where('user_id', $userId)
            ->where('start_datetime', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();

        // Completed this month
        $completed = Appointment::where('user_id', $userId)
            ->where('start_datetime', '>=', $thisMonth)
            ->where('status', 'completed')
            ->count();

        $completionRate = $thisMonthTotal > 0
            ? round(($completed / $thisMonthTotal) * 100, 1)
            : 0;

        // No-show rate
        $noShows = Appointment::where('user_id', $userId)
            ->where('start_datetime', '>=', $thisMonth)
            ->where('status', 'no_show')
            ->count();

        $noShowRate = $thisMonthTotal > 0
            ? round(($noShows / $thisMonthTotal) * 100, 1)
            : 0;

        return [
            Stat::make('Total This Month', $thisMonthTotal)
                ->description($change > 0 ? "{$change}% increase" : "{$change}% decrease")
                ->descriptionIcon($change > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change > 0 ? 'success' : 'danger')
                ->chart([7, 12, 15, 18, 22, 25, $thisMonthTotal]),

            Stat::make('Upcoming', $upcoming)
                ->description('Scheduled & confirmed')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->url(route('filament.dashboard.resources.appointments.index', ['tableFilters[upcoming][value]' => true])),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description($completed . ' completed / ' . $thisMonthTotal . ' total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger')),

            Stat::make('No-Show Rate', $noShowRate . '%')
                ->description($noShows . ' no-shows this month')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($noShowRate <= 5 ? 'success' : ($noShowRate <= 10 ? 'warning' : 'danger')),
        ];
    }
}
