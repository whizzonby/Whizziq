<?php

namespace App\Filament\Dashboard\Resources\GoalResource\Pages;

use App\Filament\Dashboard\Resources\GoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGoal extends CreateRecord
{
    protected static string $resource = GoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'in_progress';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Calculate initial progress for all key results
        foreach ($this->record->keyResults as $keyResult) {
            $keyResult->calculateProgress();
        }

        // Calculate overall goal progress
        $this->record->calculateProgress();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Goal created successfully!';
    }
}
