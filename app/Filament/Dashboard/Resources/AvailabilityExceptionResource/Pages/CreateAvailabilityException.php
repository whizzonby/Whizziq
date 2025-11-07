<?php

namespace App\Filament\Dashboard\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Dashboard\Resources\AvailabilityExceptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAvailabilityException extends CreateRecord
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return AvailabilityExceptionResource::getUrl('index');
    }
}
