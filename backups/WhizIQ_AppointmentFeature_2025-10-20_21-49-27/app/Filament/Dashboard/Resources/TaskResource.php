<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\TaskResource\Pages;
use App\Models\Goal;
use App\Models\Task;
use App\Models\TaskTag;
use App\Models\DocumentVault;
use App\Services\TaskPriorityService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Tasks & Actions';

    protected static ?string $modelLabel = 'Task';

    protected static ?string $pluralModelLabel = 'Tasks';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task Details')
                    ->description('Capture what needs to be done')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Task Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Follow up with client about proposal')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Additional context or details...')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->required()
                            ->options([
                                'urgent' => 'Urgent',
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low',
                            ])
                            ->native(false)
                            ->default('medium')
                            ->helperText(fn ($state, $record) =>
                                $record?->hasAIPriority()
                                    ? "AI suggests: {$record->ai_priority_level} ({$record->ai_priority_score}/100)"
                                    : null
                            )
                            ->columnSpan(['lg' => 1]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->native(false)
                            ->default('pending')
                            ->columnSpan(['lg' => 1]),

                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('Estimated Time (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('30')
                            ->helperText('How long will this take?')
                            ->columnSpan(['lg' => 1]),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now()->subDay())
                            ->columnSpan(['lg' => 1]),

                        Forms\Components\Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('color')
                                    ->options([
                                        'gray' => 'Gray',
                                        'primary' => 'Blue',
                                        'success' => 'Green',
                                        'warning' => 'Orange',
                                        'danger' => 'Red',
                                        'info' => 'Cyan',
                                        'purple' => 'Purple',
                                        'pink' => 'Pink',
                                    ])
                                    ->default('gray'),
                            ])
                            ->createOptionUsing(function ($data) {
                                return TaskTag::create([
                                    'user_id' => auth()->id(),
                                    'name' => $data['name'],
                                    'color' => $data['color'] ?? 'gray',
                                ])->id;
                            })
                            ->columnSpan(['lg' => 2]),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                Section::make('Linking & Context')
                    ->description('Connect this task to goals and documents')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('linked_goal_id')
                                    ->label('Link to Goal')
                                    ->options(function () {
                                        return Goal::where('user_id', auth()->id())
                                            ->active()
                                            ->pluck('title', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Connect this task to a business goal'),

                                Forms\Components\Select::make('linked_document_id')
                                    ->label('Link to Document')
                                    ->options(function () {
                                        return DocumentVault::where('user_id', auth()->id())
                                            ->latest()
                                            ->limit(100)
                                            ->pluck('file_name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Link to a related document'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->placeholder('Any other important information...')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Section::make('Reminders')
                    ->description('Set up task reminders')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Toggle::make('reminder_enabled')
                            ->label('Enable Reminder')
                            ->reactive()
                            ->inline(false),

                        Forms\Components\DateTimePicker::make('reminder_date')
                            ->label('Reminder Date & Time')
                            ->native(false)
                            ->visible(fn ($get) => $get('reminder_enabled'))
                            ->helperText('When should we remind you?'),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (Task $record): string => $record->status_icon)
                    ->color(fn (Task $record): string => $record->status_color)
                    ->tooltip(fn (Task $record): string => Str::title(str_replace('_', ' ', $record->status))),

                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Task $record): ?string => Str::limit($record->description ?? '', 50))
                    ->wrap(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->icon(fn (Task $record): string => $record->priority_icon)
                    ->color(fn (Task $record): string => $record->priority_color)
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M d')
                    ->sortable()
                    ->description(fn (Task $record): ?string =>
                        $record->due_date
                            ? ($record->isOverdue()
                                ? '⚠️ Overdue'
                                : ($record->isDueToday()
                                    ? '🔥 Today'
                                    : $record->days_until_due . ' days'
                                )
                            )
                            : null
                    )
                    ->color(fn (Task $record): string =>
                        $record->isOverdue() ? 'danger' : ($record->isDueToday() ? 'warning' : 'gray')
                    ),

                Tables\Columns\TextColumn::make('tags.name')
                    ->badge()
                    ->color(fn ($record, $state): string =>
                        $record->tags()->where('name', $state)->first()?->color ?? 'gray'
                    )
                    ->separator(',')
                    ->limit(2),

                Tables\Columns\TextColumn::make('linkedGoal.title')
                    ->label('Goal')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-flag')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('ai_priority_score')
                    ->label('AI Priority')
                    ->boolean()
                    ->trueIcon('heroicon-s-sparkles')
                    ->falseIcon('heroicon-o-sparkles')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Task $record) =>
                        $record->hasAIPriority()
                            ? "AI Score: {$record->ai_priority_score}/100 - {$record->ai_priority_level}"
                            : 'No AI analysis yet'
                    )
                    ->getStateUsing(fn (Task $record) => $record->hasAIPriority())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source')
                    ->icon(fn (Task $record): string => $record->source_icon)
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'urgent' => 'Urgent',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\Filter::make('due_today')
                    ->label('Due Today')
                    ->query(fn (Builder $query) => $query->dueToday()),

                Tables\Filters\Filter::make('high_priority')
                    ->label('High Priority')
                    ->query(fn (Builder $query) => $query->highPriority()),

                Tables\Filters\SelectFilter::make('linked_goal_id')
                    ->label('Linked Goal')
                    ->relationship('linkedGoal', 'title')
                    ->preload(),

                Tables\Filters\SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Action::make('calculate_priority')
                    ->label('AI Priority')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (Task $record) {
                        $service = app(TaskPriorityService::class);
                        $priority = $service->calculatePriorityScore($record);

                        $record->update([
                            'ai_priority_score' => $priority['score'],
                            'ai_priority_reasoning' => $priority['reasoning'],
                        ]);

                        Notification::make()
                            ->title('AI Priority Calculated')
                            ->body("Score: {$priority['score']}/100 - {$priority['reasoning']}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(false)
                    ->visible(fn (Task $record) => $record->status !== 'completed'),

                Action::make('mark_complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Task $record) => $record->markAsCompleted())
                    ->requiresConfirmation(false)
                    ->visible(fn (Task $record) => $record->status !== 'completed'),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Task $record) => TaskResource::getUrl('view', ['record' => $record])),

                EditAction::make()
                    ->color('gray'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('calculate_priorities')
                        ->label('Calculate AI Priorities')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->action(function ($records) {
                            $service = app(TaskPriorityService::class);
                            $service->batchCalculatePriorities($records);

                            Notification::make()
                                ->title('AI Priorities Calculated')
                                ->body('Priority scores updated for selected tasks')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('mark_complete')
                        ->label('Mark as Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markAsCompleted();
                            }
                        })
                        ->requiresConfirmation(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No tasks yet')
            ->emptyStateDescription('Start capturing your action items and to-dos')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create First Task')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->where('status', '!=', 'completed')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::where('user_id', auth()->id())
            ->overdue()
            ->count();

        return $overdue > 0 ? 'danger' : 'success';
    }
}
