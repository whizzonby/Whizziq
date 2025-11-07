<?php

namespace App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;

use App\Filament\Dashboard\Resources\DocumentVaultResource;
use Filament\Notifications\Notification;
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
            $fullPath = Storage::disk('public')->path($filePath);
            $fileInfo = pathinfo($fullPath);

            $data['file_name'] = $fileInfo['basename'];
            $data['file_type'] = strtolower($fileInfo['extension'] ?? 'unknown');
            $data['mime_type'] = Storage::disk('public')->mimeType($filePath) ?? 'application/octet-stream';
            $fileSize = Storage::disk('public')->size($filePath);
            $data['file_size'] = $fileSize;


            // Calculate file hash for duplicate detection
            $data['file_hash'] = hash_file('sha256', $fullPath);

            // Initialize version number
            $data['version_number'] = 1;
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
