<?php

namespace App\Filament\Dashboard\Resources\GoalResource\Pages;

use App\Filament\Dashboard\Resources\GoalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoals extends ListRecords
{
    protected static string $resource = GoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Goals Dashboard')
                ->icon('heroicon-o-chart-bar-square')
                ->color('primary')
                ->url(route('filament.dashboard.pages.goals-dashboard')),

            Actions\CreateAction::make()
                ->label('Create Goal')
                ->icon('heroicon-o-plus'),
        ];
    }
}
