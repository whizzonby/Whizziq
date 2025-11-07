<?php

namespace App\Filament\Dashboard\Resources\StaffMetricResource\Pages;

use App\Filament\Dashboard\Resources\StaffMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffMetrics extends ListRecords
{
    protected static string $resource = StaffMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


