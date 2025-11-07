<?php

namespace App\Filament\Dashboard\Resources\ContactSegmentResource\Pages;

use App\Filament\Dashboard\Resources\ContactSegmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactSegment extends EditRecord
{
    protected static string $resource = ContactSegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Update contact count after saving
        $this->record->updateContactCount();
    }
}
