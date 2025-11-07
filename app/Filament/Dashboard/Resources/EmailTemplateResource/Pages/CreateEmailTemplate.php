<?php

namespace App\Filament\Dashboard\Resources\EmailTemplateResource\Pages;

use App\Filament\Dashboard\Resources\EmailTemplateResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function mount(): void
    {
        if (!EmailTemplateResource::canCreate()) {
            Notification::make()
                ->title('Subscription Required')
                ->body('Please subscribe to a plan to create email templates. Choose a plan that fits your needs!')
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
        return $this->getResource()::getUrl('index');
    }
}
