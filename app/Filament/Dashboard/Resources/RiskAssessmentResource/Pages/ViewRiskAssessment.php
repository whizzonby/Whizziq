<?php

namespace App\Filament\Dashboard\Resources\RiskAssessmentResource\Pages;

use App\Filament\Dashboard\Resources\RiskAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRiskAssessment extends ViewRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('gray'),

            Actions\DeleteAction::make()
                ->successRedirectUrl(RiskAssessmentResource::getUrl('index')),
        ];
    }
}

