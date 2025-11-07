<?php

namespace App\Filament\Dashboard\Resources\VenueResource\Pages;

use App\Filament\Dashboard\Resources\VenueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}


