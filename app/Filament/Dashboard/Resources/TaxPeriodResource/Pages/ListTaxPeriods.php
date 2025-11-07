<?php

namespace App\Filament\Dashboard\Resources\TaxPeriodResource\Pages;

use App\Filament\Dashboard\Resources\TaxPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxPeriods extends ListRecords
{
    protected static string $resource = TaxPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
