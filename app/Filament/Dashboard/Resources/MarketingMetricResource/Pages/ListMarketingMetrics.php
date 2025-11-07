<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Pages;

use App\Filament\Dashboard\Resources\MarketingMetricResource;
use App\Filament\Dashboard\Resources\MarketingMetricResource\Widgets\MarketingMetricsSummaryWidget;
use App\Filament\Dashboard\Widgets\ConversionFunnelWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingMetrics extends ListRecords
{
    protected static string $resource = MarketingMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MarketingMetricsSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ConversionFunnelWidget::class,
        ];
    }
}
