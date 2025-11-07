<?php

namespace App\Filament\Dashboard\Resources\BusinessMetricResource\Pages;

use App\Filament\Dashboard\Resources\BusinessMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessMetrics extends ListRecords
{
    protected static string $resource = BusinessMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
