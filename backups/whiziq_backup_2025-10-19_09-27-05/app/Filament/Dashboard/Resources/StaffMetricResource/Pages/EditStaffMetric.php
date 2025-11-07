<?php

namespace App\Filament\Dashboard\Resources\StaffMetricResource\Pages;

use App\Filament\Dashboard\Resources\StaffMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffMetric extends EditRecord
{
    protected static string $resource = StaffMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}


