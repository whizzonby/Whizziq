<?php

namespace App\Filament\Dashboard\Resources\BusinessMetricResource\Pages;

use App\Filament\Dashboard\Resources\BusinessMetricResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessMetric extends CreateRecord
{
    protected static string $resource = BusinessMetricResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
