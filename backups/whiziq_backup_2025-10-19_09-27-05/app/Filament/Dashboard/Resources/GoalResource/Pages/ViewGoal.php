<?php

namespace App\Filament\Dashboard\Resources\GoalResource\Pages;

use App\Filament\Dashboard\Resources\GoalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoal extends ViewRecord
{
    protected static string $resource = GoalResource::class;

    protected string $view = 'filament.dashboard.resources.goal-resource.view-goal';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_in')
                ->label('Weekly Check-in')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->needsCheckIn())
                ->url(fn () => GoalResource::getUrl('check-in', ['record' => $this->getRecord()])),

            Actions\EditAction::make()
                ->color('gray'),

            Actions\DeleteAction::make()
                ->successRedirectUrl(GoalResource::getUrl('index')),
        ];
    }
}
