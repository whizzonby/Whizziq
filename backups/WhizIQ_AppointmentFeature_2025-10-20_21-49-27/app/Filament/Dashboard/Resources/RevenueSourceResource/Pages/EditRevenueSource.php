<?php

namespace App\Filament\Dashboard\Resources\RevenueSourceResource\Pages;

use App\Filament\Dashboard\Resources\RevenueSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevenueSource extends EditRecord
{
    protected static string $resource = RevenueSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
