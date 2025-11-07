<?php

namespace App\Filament\Dashboard\Resources\GoalResource\Pages;

use App\Filament\Dashboard\Resources\GoalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoal extends EditRecord
{
    protected static string $resource = GoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('View Goal')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn () => GoalResource::getUrl('view', ['record' => $this->getRecord()])),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Recalculate progress after edits
        foreach ($this->record->keyResults as $keyResult) {
            $keyResult->calculateProgress();
        }

        $this->record->calculateProgress();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
