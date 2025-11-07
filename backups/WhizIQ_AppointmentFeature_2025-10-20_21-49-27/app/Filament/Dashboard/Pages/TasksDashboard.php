<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\HighPriorityTasksWidget;
use App\Filament\Dashboard\Widgets\TaskCompletionChartWidget;
use App\Filament\Dashboard\Widgets\TasksOverviewWidget;
use App\Filament\Dashboard\Widgets\UpcomingTasksWidget;
use Filament\Pages\Page;
use BackedEnum;

class TasksDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Tasks Dashboard';

    protected static ?string $title = 'Tasks Dashboard';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.dashboard.pages.tasks-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            TasksOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            UpcomingTasksWidget::class,
            HighPriorityTasksWidget::class,
            TaskCompletionChartWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 4;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }
}
