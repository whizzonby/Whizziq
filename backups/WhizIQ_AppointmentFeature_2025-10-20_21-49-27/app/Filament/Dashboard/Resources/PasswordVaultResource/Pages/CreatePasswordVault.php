<?php

namespace App\Filament\Dashboard\Resources\PasswordVaultResource\Pages;

use App\Filament\Dashboard\Resources\PasswordVaultResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePasswordVault extends CreateRecord
{
    protected static string $resource = PasswordVaultResource::class;

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
