<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Dashboard\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class HighPriorityTasksWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('user_id', auth()->id())
                    ->highPriority()
                    ->orderByRaw("FIELD(priority, 'urgent', 'high')")
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50)
                    ->description(fn (Task $record): ?string => Str::limit($record->description ?? '', 60)),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->icon(fn (Task $record): string => $record->priority_icon)
                    ->color(fn (Task $record): string => $record->priority_color)
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M d')
                    ->description(fn (Task $record): ?string =>
                        $record->due_date
                            ? ($record->isOverdue()
                                ? '⚠️ Overdue'
                                : ($record->isDueToday()
                                    ? '🔥 Today'
                                    : $record->days_until_due . ' days'
                                )
                            )
                            : 'No due date'
                    )
                    ->color(fn (Task $record): string =>
                        $record->isOverdue() ? 'danger' : ($record->isDueToday() ? 'warning' : 'gray')
                    ),

                Tables\Columns\IconColumn::make('ai_priority_score')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-s-sparkles')
                    ->falseIcon('heroicon-o-sparkles')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Task $record) =>
                        $record->hasAIPriority()
                            ? "AI: {$record->ai_priority_score}/100"
                            : 'No AI analysis'
                    )
                    ->getStateUsing(fn (Task $record) => $record->hasAIPriority()),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Done')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Task $record) => $record->markAsCompleted())
                    ->requiresConfirmation(false),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Task $record) => TaskResource::getUrl('view', ['record' => $record])),
            ])
            ->heading('🔥 High Priority Tasks')
            ->description('Urgent and high priority tasks requiring attention')
            ->emptyStateHeading('No high priority tasks')
            ->emptyStateDescription('Great! Nothing urgent right now.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
