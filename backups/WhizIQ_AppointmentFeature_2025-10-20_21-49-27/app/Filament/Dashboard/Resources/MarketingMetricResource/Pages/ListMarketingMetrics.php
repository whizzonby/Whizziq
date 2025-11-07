<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Pages;

use App\Filament\Dashboard\Resources\MarketingMetricResource;
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
}
