<?php

namespace App\Filament\Dashboard\Resources\ContactResource\Pages;

use App\Filament\Dashboard\Resources\ContactResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    public function mount(): void
    {
        if (!ContactResource::canCreate()) {
            Notification::make()
                ->title('Subscription Required')
                ->body('Please subscribe to a plan to create contacts. Choose a plan that fits your needs!')
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
        return ContactResource::getUrl('index');
    }
}
