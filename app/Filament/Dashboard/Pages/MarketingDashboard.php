<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\AutomatedInsightsWidget;
use App\Filament\Dashboard\Widgets\ChannelComparisonWidget;
use App\Filament\Dashboard\Widgets\CLVvsCACWidget;
use App\Filament\Dashboard\Widgets\ConversionFunnelWidget;
use App\Filament\Dashboard\Widgets\EngagementTrafficWidget;
use App\Filament\Dashboard\Widgets\MarketingInsightsWidget;
use App\Filament\Dashboard\Widgets\MarketingMetricsWidget;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class MarketingDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Marketing Dashboard';

    protected static ?string $title = 'Marketing Analytics Dashboard';

    protected static UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.dashboard.pages.marketing-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            MarketingInsightsWidget::class,
            AutomatedInsightsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            EngagementTrafficWidget::class,
            MarketingMetricsWidget::class,
            ChannelComparisonWidget::class,
            ConversionFunnelWidget::class,
            CLVvsCACWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 3,
        ];
    }
}

