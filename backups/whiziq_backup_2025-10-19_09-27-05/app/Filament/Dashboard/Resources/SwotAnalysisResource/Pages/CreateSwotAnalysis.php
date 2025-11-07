<?php

namespace App\Filament\Dashboard\Resources\SwotAnalysisResource\Pages;

use App\Filament\Dashboard\Resources\SwotAnalysisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSwotAnalysis extends CreateRecord
{
    protected static string $resource = SwotAnalysisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
