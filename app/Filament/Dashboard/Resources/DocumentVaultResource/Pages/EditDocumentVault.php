<?php

namespace App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;

use App\Filament\Dashboard\Resources\DocumentVaultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentVault extends EditRecord
{
    protected static string $resource = DocumentVaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('View Document')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn () => DocumentVaultResource::getUrl('view', ['record' => $this->getRecord()])),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
