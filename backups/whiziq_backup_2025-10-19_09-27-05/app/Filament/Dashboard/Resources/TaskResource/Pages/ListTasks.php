<?php

namespace App\Filament\Dashboard\Resources\TaskResource\Pages;

use App\Filament\Dashboard\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskExtractionService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_capture')
                ->label('Quick Capture')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('text')
                        ->label('Paste Text or Meeting Notes')
                        ->rows(5)
                        ->required()
                        ->placeholder('Paste meeting notes, email, or quick thoughts... AI will extract action items automatically.')
                        ->helperText('AI will scan for tasks like "follow up with John" or "review proposal by Friday"'),
                ])
                ->action(function ($data) {
                    $service = app(TaskExtractionService::class);
                    $tasks = $service->extractFromText($data['text'], auth()->user());

                    if (empty($tasks)) {
                        Notification::make()
                            ->title('No Action Items Found')
                            ->body('AI couldn\'t identify specific tasks in your text. Try adding a task manually.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tasks Extracted!')
                            ->body(count($tasks) . ' action item(s) have been added to your task list.')
                            ->success()
                            ->send();
                    }
                })
                ->modalWidth('lg')
                ->modalIcon('heroicon-o-sparkles')
                ->modalHeading('AI Quick Capture'),

            Actions\CreateAction::make()
                ->label('New Task')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Can add overview widgets here
        ];
    }
}
