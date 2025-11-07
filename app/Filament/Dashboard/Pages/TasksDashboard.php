<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\HighPriorityTasksWidget;
use App\Filament\Dashboard\Widgets\TaskCompletionChartWidget;
use App\Filament\Dashboard\Widgets\TasksOverviewWidget;
use App\Filament\Dashboard\Widgets\UpcomingTasksWidget;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class TasksDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Tasks Dashboard';
    protected static UnitEnum|string|null $navigationGroup = 'Productivity';

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
            HighPriorityTasksWidget::class,
            // TODO: Create UpcomingTasksWidget and TaskCompletionChartWidget for additional insights
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
