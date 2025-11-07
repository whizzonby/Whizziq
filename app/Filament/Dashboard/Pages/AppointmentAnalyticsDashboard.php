<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\AppointmentStatsWidget;
use App\Filament\Dashboard\Widgets\AppointmentsChartWidget;
use App\Filament\Dashboard\Widgets\RecentAppointmentsWidget;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class AppointmentAnalyticsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Appointment Analytics';

    protected static ?string $title = 'Appointment Analytics & Insights';

    protected static UnitEnum|string|null $navigationGroup = 'Booking';

    //protected static ?int $navigationSort = 4;

    protected string $view = 'filament.dashboard.pages.appointment-analytics-dashboard';


    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AppointmentsChartWidget::class,
            RecentAppointmentsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
