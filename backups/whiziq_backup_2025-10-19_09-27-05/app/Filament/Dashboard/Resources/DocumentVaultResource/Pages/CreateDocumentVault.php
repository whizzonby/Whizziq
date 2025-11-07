<?php

namespace App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;

use App\Filament\Dashboard\Resources\DocumentVaultResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocumentVault extends CreateRecord
{
    protected static string $resource = DocumentVaultResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Extract file information
        if (isset($data['file_path'])) {
            $filePath = $data['file_path'];

            // Get file info from storage
            $fullPath = Storage::path($filePath);
            $fileInfo = pathinfo($fullPath);

            $data['file_name'] = $fileInfo['basename'];
            $data['file_type'] = strtolower($fileInfo['extension'] ?? 'unknown');
            $data['mime_type'] = Storage::mimeType($filePath) ?? 'application/octet-stream';
            $data['file_size'] = Storage::size($filePath);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Document uploaded successfully!';
    }
}
