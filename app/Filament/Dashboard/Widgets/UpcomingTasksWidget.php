<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Dashboard\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class UpcomingTasksWidget extends BaseWidget
{
    protected static ?int $sort = 2;


    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('user_id', auth()->id())
                    ->where('status', '!=', 'completed')
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', now())
                    ->orderBy('due_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (Task $record): string => $record->status_icon)
                    ->color(fn (Task $record): string => $record->status_color)
                    ->tooltip(fn (Task $record): string => Str::title(str_replace('_', ' ', $record->status))),

                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->icon(fn (Task $record): string => $record->priority_icon)
                    ->color(fn (Task $record): string => $record->priority_color)
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M d')
                    ->description(fn (Task $record): ?string =>
                        $record->isDueToday()
                            ? '🔥 Today'
                            : $record->days_until_due . ' days'
                    )
                    ->color(fn (Task $record): string =>
                        $record->isDueToday() ? 'warning' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('estimated_time_human')
                    ->label('Time')
                    ->badge()
                    ->color('gray'),
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
            ->heading('📅 Upcoming Tasks')
            ->description('Tasks due soon, sorted by date')
            ->emptyStateHeading('No upcoming tasks')
            ->emptyStateDescription('You\'re all caught up!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
