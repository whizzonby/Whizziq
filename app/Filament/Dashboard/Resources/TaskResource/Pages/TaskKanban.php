<?php

namespace App\Filament\Dashboard\Resources\TaskResource\Pages;

use App\Filament\Dashboard\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskTag;
use App\Models\Goal;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

class TaskKanban extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TaskResource::class;

    protected static ?string $title = 'Task Board';

    public function getView(): string
    {
        return 'filament.dashboard.resources.task-resource.pages.task-kanban-simple';
    }

    protected static ?string $slug = 'kanban';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        // Simple mount method
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(route('filament.dashboard.resources.tasks.index')),
        ];
    }
}
