<?php

namespace App\Filament\Dashboard\Resources\TaxPeriodResource\Pages;

use App\Filament\Dashboard\Resources\TaxPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxPeriod extends CreateRecord
{
    protected static string $resource = TaxPeriodResource::class;

    protected function getRedirectUrl(): string
    {
        return TaxPeriodResource::getUrl('index');
    }
}
