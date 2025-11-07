<?php

namespace App\Filament\Dashboard\Resources\SwotAnalysisResource\Pages;

use App\Filament\Dashboard\Resources\SwotAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSwotAnalysis extends EditRecord
{
    protected static string $resource = SwotAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
