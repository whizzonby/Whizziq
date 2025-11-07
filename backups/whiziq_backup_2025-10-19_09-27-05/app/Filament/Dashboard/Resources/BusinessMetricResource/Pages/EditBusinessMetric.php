<?php

namespace App\Filament\Dashboard\Resources\BusinessMetricResource\Pages;

use App\Filament\Dashboard\Resources\BusinessMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessMetric extends EditRecord
{
    protected static string $resource = BusinessMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
