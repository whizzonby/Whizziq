<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $total = Task::where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->count();

        $dueToday = Task::where('user_id', $user->id)
            ->dueToday()
            ->count();

        $overdue = Task::where('user_id', $user->id)
            ->overdue()
            ->count();

        $completedThisWeek = Task::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->startOfWeek())
            ->count();

        $highPriority = Task::where('user_id', $user->id)
            ->highPriority()
            ->count();

        return [
            Stat::make('Active Tasks', $total)
                ->description('Total pending & in-progress')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Due Today', $dueToday)
                ->description('Tasks due today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($dueToday > 0 ? 'warning' : 'success'),

            Stat::make('Overdue', $overdue)
                ->description('Need immediate attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'gray'),

            Stat::make('Completed This Week', $completedThisWeek)
                ->description('Tasks finished this week')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
