<?php

namespace App\Filament\Dashboard\Resources\InvoiceClientResource\Pages;

use App\Filament\Dashboard\Resources\InvoiceClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceClients extends ListRecords
{
    protected static string $resource = InvoiceClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
