<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\Task;
use App\Models\TaskTag;
use App\Models\Goal;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use UnitEnum;
use BackedEnum;

class TaskBoardPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.dashboard.pages.task-board-page';

    protected static ?string $navigationLabel = 'Task Board';

    protected static UnitEnum|string|null $navigationGroup = 'Productivity';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Task Board';


    public array $statusCounts = [];
    public array $filterValues = [
        'priority' => [],
        'tags' => [],
        'goal' => null,
        'overdue_only' => false,
    ];

    public function mount(): void
    {
        $this->loadStatusCounts();
    }

    public function loadStatusCounts(): void
    {
        $query = $this->getFilteredQuery();

        $this->statusCounts = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    protected function getFilteredQuery()
    {
        $query = Task::where('user_id', auth()->id());

        // Priority filter
        if (!empty($this->filterValues['priority'])) {
            $query->whereIn('priority', $this->filterValues['priority']);
        }

        // Tags filter
        if (!empty($this->filterValues['tags'])) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('task_tags.id', $this->filterValues['tags']);
            });
        }

        // Goal filter
        if ($this->filterValues['goal']) {
            $query->where('linked_goal_id', $this->filterValues['goal']);
        }

        // Overdue only filter
        if ($this->filterValues['overdue_only']) {
            $query->overdue();
        }

        return $query;
    }

    public function getTasksByStatus(string $status): array
    {
        return $this->getFilteredQuery()
            ->where('status', $status)
            ->with(['tags', 'linkedGoal'])
            ->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END
            ")
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'priority_color' => $task->priority_color,
                'priority_icon' => $task->priority_icon,
                'due_date' => $task->due_date?->format('M d'),
                'is_overdue' => $task->isOverdue(),
                'is_due_today' => $task->isDueToday(),
                'days_until_due' => $task->days_until_due,
                'tags' => $task->tags->map(fn ($tag) => [
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])->toArray(),
                'linked_goal' => $task->linkedGoal ? [
                    'title' => $task->linkedGoal->title,
                ] : null,
                'ai_priority_score' => $task->ai_priority_score,
                'ai_priority_level' => $task->ai_priority_level,
                'estimated_time_human' => $task->estimated_time_human,
            ])
            ->toArray();
    }

    public function updateTaskStatus(int $taskId, string $newStatus): void
    {
        $task = Task::where('user_id', auth()->id())->findOrFail($taskId);

        $task->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === 'completed' ? now() : null,
        ]);

        $this->loadStatusCounts();

        Notification::make()
            ->title('Task Updated')
            ->body("Task moved to " . ucfirst(str_replace('_', ' ', $newStatus)))
            ->success()
            ->send();

        $this->dispatch('task-updated');
    }

    public function applyFilters(): void
    {
        $this->loadStatusCounts();
        $this->dispatch('filters-applied');
    }

    public function clearFilters(): void
    {
        $this->filterValues = [
            'priority' => [],
            'tags' => [],
            'goal' => null,
            'overdue_only' => false,
        ];

        $this->loadStatusCounts();
        $this->dispatch('filters-applied');
    }

    public function getPriorityOptions(): array
    {
        return [
            'urgent' => 'Urgent',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];
    }

    public function getTagOptions(): array
    {
        return TaskTag::where('user_id', auth()->id())
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getGoalOptions(): array
    {
        return Goal::where('user_id', auth()->id())
            ->active()
            ->pluck('title', 'id')
            ->toArray();
    }

    public function editTask(int $taskId): void
    {
        $this->redirect(route('filament.dashboard.resources.tasks.edit', ['record' => $taskId]));
    }

    public function createTask(?string $status = 'pending'): void
    {
        $this->redirect(route('filament.dashboard.resources.tasks.create', ['status' => $status]));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Task::where('user_id', auth()->id())
            ->where('status', '!=', 'completed')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = Task::where('user_id', auth()->id())
            ->overdue()
            ->count();

        return $overdue > 0 ? 'danger' : 'success';
    }
}
