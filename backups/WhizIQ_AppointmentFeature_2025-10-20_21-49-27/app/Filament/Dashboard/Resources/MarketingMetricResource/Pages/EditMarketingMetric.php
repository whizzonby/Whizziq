<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Pages;

use App\Filament\Dashboard\Resources\MarketingMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingMetric extends EditRecord
{
    protected static string $resource = MarketingMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
