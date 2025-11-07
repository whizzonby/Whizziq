<?php

namespace App\Filament\Dashboard\Resources\ContactSegmentResource\Pages;

use App\Filament\Dashboard\Resources\ContactSegmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactSegments extends ListRecords
{
    protected static string $resource = ContactSegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
