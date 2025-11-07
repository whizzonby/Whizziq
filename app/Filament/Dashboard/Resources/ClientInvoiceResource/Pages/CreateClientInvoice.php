<?php

namespace App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;

use App\Filament\Dashboard\Resources\ClientInvoiceResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClientInvoice extends CreateRecord
{
    protected static string $resource = ClientInvoiceResource::class;

    public function mount(): void
    {
        if (!ClientInvoiceResource::canCreate()) {
            Notification::make()
                ->title('Subscription Required')
                ->body('Please subscribe to a plan to create invoices. Choose a plan that fits your needs!')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(SubscriptionResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
