<?php

namespace App\Filament\Dashboard\Resources\InvoiceClientResource\Pages;

use App\Filament\Dashboard\Resources\InvoiceClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceClient extends CreateRecord
{
    protected static string $resource = InvoiceClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return InvoiceClientResource::getUrl('index');
    }
}
