<?php

namespace App\Filament\Dashboard\Resources\TaxDocumentResource\Pages;

use App\Filament\Dashboard\Resources\TaxDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxDocument extends CreateRecord
{
    protected static string $resource = TaxDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Set file metadata if file was uploaded
        if (isset($data['file_path']) && is_string($data['file_path'])) {
            $filePath = storage_path('app/public/' . $data['file_path']);
            if (file_exists($filePath)) {
                $data['file_size'] = filesize($filePath);
                $data['file_type'] = pathinfo($filePath, PATHINFO_EXTENSION);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Trigger OCR processing if enabled
        if (config('tax.features.document_ocr')) {
            $ocrService = app(\App\Services\DocumentOCRService::class);
            $ocrService->queueDocumentForProcessing($this->record);
        }
    }

    protected function getRedirectUrl(): string
    {
        return TaxDocumentResource::getUrl('index');
    }
}
