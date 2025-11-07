<?php

namespace App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;

use App\Filament\Dashboard\Resources\ClientInvoiceResource;
use App\Models\ClientInvoice;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClientInvoice extends EditRecord
{
    protected static string $resource = ClientInvoiceResource::class;

    public function mount(int|string $record): void
    {
        // First, resolve the model properly
        $this->record = $this->resolveRecord($record);
        
        // Then redirect to Invoice Builder with invoice ID
        $this->redirect(route('filament.dashboard.pages.invoice-builder-page', ['invoice' => $record]));
    }

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
