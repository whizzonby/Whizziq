<?php

namespace App\Filament\Dashboard\Resources\RiskAssessmentResource\Pages;

use App\Filament\Dashboard\Resources\RiskAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRiskAssessment extends CreateRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
