<?php

namespace App\Filament\Dashboard\Resources\TaskResource\Pages;

use App\Filament\Dashboard\Resources\TaskResource;
use App\Services\TaskPriorityService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.dashboard.resources.task-resource.view-task';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calculate_priority')
                ->label('Calculate AI Priority')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(function () {
                    $service = app(TaskPriorityService::class);
                    $priority = $service->calculatePriorityScore($this->record);

                    $this->record->update([
                        'ai_priority_score' => $priority['score'],
                        'ai_priority_reasoning' => $priority['reasoning'],
                    ]);

                    Notification::make()
                        ->title('AI Priority Calculated')
                        ->body("Score: {$priority['score']}/100")
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'ai_priority_score',
                        'ai_priority_reasoning',
                    ]);
                })
                ->visible(fn () => $this->record->status !== 'completed'),

            Actions\Action::make('mark_complete')
                ->label($this->record->status === 'completed' ? 'Completed' : 'Mark as Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->markAsCompleted();

                    Notification::make()
                        ->title('Task Completed!')
                        ->success()
                        ->send();

                    return redirect(TaskResource::getUrl('index'));
                })
                ->disabled($this->record->status === 'completed')
                ->requiresConfirmation(false),

            Actions\EditAction::make()
                ->color('gray'),

            Actions\DeleteAction::make()
                ->successRedirectUrl(TaskResource::getUrl('index')),
        ];
    }
}
