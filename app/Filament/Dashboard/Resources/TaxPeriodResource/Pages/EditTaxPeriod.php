<?php

namespace App\Filament\Dashboard\Resources\TaxPeriodResource\Pages;

use App\Filament\Dashboard\Resources\TaxPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxPeriod extends EditRecord
{
    protected static string $resource = TaxPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
