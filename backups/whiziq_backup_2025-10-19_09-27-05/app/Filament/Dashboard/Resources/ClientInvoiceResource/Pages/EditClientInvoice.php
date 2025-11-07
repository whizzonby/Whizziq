<?php

namespace App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;

use App\Filament\Dashboard\Resources\ClientInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClientInvoice extends EditRecord
{
    protected static string $resource = ClientInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // Recalculate totals after save
        $this->record->refresh();
        $this->record->calculateTotals();
        $this->record->save();
    }
}
