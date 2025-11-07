<?php

namespace App\Filament\Dashboard\Resources\RiskAssessmentResource\Pages;

use App\Filament\Dashboard\Resources\RiskAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRiskAssessments extends ListRecords
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
