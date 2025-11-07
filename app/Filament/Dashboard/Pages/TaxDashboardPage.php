<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\TaxForecastWidget;
use App\Filament\Dashboard\Widgets\TaxSummaryWidget;
use App\Filament\Dashboard\Widgets\UpcomingFilingDeadlinesWidget;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class TaxDashboardPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Tax & Compliance';
    protected static UnitEnum|string|null $navigationGroup = 'Tax & Compliance';

    protected static ?string $title = 'T&C Dashboard';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.dashboard.pages.tax-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            TaxSummaryWidget::class,
            TaxForecastWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            UpcomingFilingDeadlinesWidget::class,
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

    /**
     * Hide navigation for users without access
     * Tax Dashboard is a Pro+ feature
     */

}
