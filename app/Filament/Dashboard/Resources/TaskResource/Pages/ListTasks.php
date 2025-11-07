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
            Actions\Action::make('kanban_view')
                ->label('Board View')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(route('filament.dashboard.pages.task-board-page')),

            Actions\Action::make('quick_capture')
                ->label('Quick Capture (AI)')
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
                    try {
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
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        
                        // Provide user-friendly error messages
                        if (str_contains($errorMessage, 'API key') || str_contains($errorMessage, 'OPENAI_API_KEY')) {
                            Notification::make()
                                ->title('AI Service Not Configured')
                                ->body('OpenAI API key is not configured. Please contact your administrator to set up the AI service.')
                                ->danger()
                                ->persistent()
                                ->send();
                        } elseif (str_contains($errorMessage, 'limit reached') || str_contains($errorMessage, 'not available')) {
                            Notification::make()
                                ->title('AI Service Limit Reached')
                                ->body($errorMessage)
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('AI Service Error')
                                ->body('Unable to extract tasks at this time. Please try again later or add tasks manually.')
                                ->danger()
                                ->send();
                        }
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
