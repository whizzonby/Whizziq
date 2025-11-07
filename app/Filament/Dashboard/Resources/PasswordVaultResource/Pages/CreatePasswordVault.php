<?php

namespace App\Filament\Dashboard\Resources\PasswordVaultResource\Pages;

use App\Filament\Dashboard\Resources\PasswordVaultResource;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePasswordVault extends CreateRecord
{
    protected static string $resource = PasswordVaultResource::class;

    public function mount(): void
    {
        if (!PasswordVaultResource::canCreate()) {
            Notification::make()
                ->title('Subscription Required')
                ->body('Please subscribe to a plan to create password entries. Choose a plan that fits your needs!')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(SubscriptionResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        // Handle password encryption manually
        if (isset($data['password']) && !empty($data['password'])) {
            $data['encrypted_password'] = \Illuminate\Support\Facades\Crypt::encryptString($data['password']);
            unset($data['password']); // Remove the plain password
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
