<?php

namespace App\Filament\Dashboard\Resources\TaxDocumentResource\Pages;

use App\Filament\Dashboard\Resources\TaxDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxDocument extends EditRecord
{
    protected static string $resource = TaxDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn() => \Storage::download($this->record->file_path, $this->record->document_name))
                ->color('primary'),

            Actions\DeleteAction::make(),
        ];
    }
}
