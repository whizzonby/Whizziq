<?php

namespace App\Filament\Dashboard\Resources\InvoiceClientResource\Pages;

use App\Filament\Dashboard\Resources\InvoiceClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceClient extends EditRecord
{
    protected static string $resource = InvoiceClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
