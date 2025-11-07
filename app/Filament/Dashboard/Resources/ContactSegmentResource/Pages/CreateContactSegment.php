<?php

namespace App\Filament\Dashboard\Resources\ContactSegmentResource\Pages;

use App\Filament\Dashboard\Resources\ContactSegmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactSegment extends CreateRecord
{
    protected static string $resource = ContactSegmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        // Update contact count after creating
        $this->record->updateContactCount();
    }

    protected function getRedirectUrl(): string
    {
        return ContactSegmentResource::getUrl('index');
    }
}
