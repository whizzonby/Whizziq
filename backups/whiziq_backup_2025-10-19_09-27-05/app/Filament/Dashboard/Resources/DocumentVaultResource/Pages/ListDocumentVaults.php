<?php

namespace App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;

use App\Filament\Dashboard\Resources\DocumentVaultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentVaults extends ListRecords
{
    protected static string $resource = DocumentVaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Upload Document')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
