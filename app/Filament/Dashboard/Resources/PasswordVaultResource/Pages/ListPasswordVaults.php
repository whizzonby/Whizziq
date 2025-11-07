<?php

namespace App\Filament\Dashboard\Resources\PasswordVaultResource\Pages;

use App\Filament\Dashboard\Resources\PasswordVaultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPasswordVaults extends ListRecords
{
    protected static string $resource = PasswordVaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Password')
                ->icon('heroicon-o-plus'),
        ];
    }
}
