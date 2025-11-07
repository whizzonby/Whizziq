<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\ActiveGoalsWidget;
use App\Filament\Dashboard\Widgets\GoalProgressChartWidget;
use App\Filament\Dashboard\Widgets\GoalsOverviewWidget;
use App\Filament\Dashboard\Widgets\OffTrackGoalsWidget;
use Filament\Pages\Page;
use BackedEnum;

class GoalsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Goals Dashboard';

    protected static ?string $title = 'Goals Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.dashboard.pages.goals-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            GoalsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ActiveGoalsWidget::class,
            OffTrackGoalsWidget::class,
            GoalProgressChartWidget::class,
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
