<?php

namespace App\Filament\Dashboard\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Dashboard\Resources\AvailabilityExceptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityException extends EditRecord
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
