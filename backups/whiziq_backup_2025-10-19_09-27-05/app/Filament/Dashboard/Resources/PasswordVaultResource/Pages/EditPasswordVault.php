<?php

namespace App\Filament\Dashboard\Resources\PasswordVaultResource\Pages;

use App\Filament\Dashboard\Resources\PasswordVaultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPasswordVault extends EditRecord
{
    protected static string $resource = PasswordVaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle password encryption manually
        if (isset($data['password']) && !empty($data['password'])) {
            $data['encrypted_password'] = \Illuminate\Support\Facades\Crypt::encryptString($data['password']);
        }
        // Always remove the password field to avoid conflicts
        unset($data['password']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
