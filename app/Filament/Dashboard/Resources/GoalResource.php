<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\GoalResource\Pages;
use App\Models\Goal;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Goals Tracker';
    protected static UnitEnum|string|null $navigationGroup = 'Productivity';

    protected static ?string $modelLabel = 'Goal';

    protected static ?string $pluralModelLabel = 'Goals';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Goal Information')
                    ->description('Define your business objective')
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Goal Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Reach $100K Monthly Revenue')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe what success looks like and why this goal matters...')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Goal Period')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly Goal',
                                'quarterly' => 'Quarterly Goal (3 months)',
                                'annual' => 'Annual Goal (12 months)',
                            ])
                            ->native(false)
                            ->default('quarterly')
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'revenue' => 'Revenue & Sales',
                                'customers' => 'Customers & Growth',
                                'product' => 'Product & Features',
                                'team' => 'Team & Hiring',
                                'operational' => 'Operations & Efficiency',
                            ])
                            ->native(false)
                            ->searchable()
                            ->placeholder('Select category')
                            ->columnSpan(1),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(now())
                                    ->reactive(),

                                Forms\Components\DatePicker::make('target_date')
                                    ->label('Target Date')
                                    ->required()
                                    ->after('start_date')
                                    ->helperText('When do you want to achieve this goal?'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Key Results')
                    ->description('Break down your goal into measurable outcomes (OKR style)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->schema([
                        Forms\Components\Repeater::make('keyResults')
                            ->relationship('keyResults')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Key Result')
                                    ->required()
                                    ->placeholder('e.g., Increase monthly signups to 500')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('metric_type')
                                    ->label('Metric Type')
                                    ->required()
                                    ->options([
                                        'number' => 'Number',
                                        'currency' => 'Currency ($)',
                                        'percentage' => 'Percentage (%)',
                                    ])
                                    ->native(false)
                                    ->default('number')
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->placeholder('e.g., customers, users, sales')
                                    ->visible(fn (Get $get) => $get('metric_type') === 'number')
                                    ->columnSpan(1),

                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('start_value')
                                            ->label('Starting Value')
                                            ->required()
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Current baseline'),

                                        Forms\Components\TextInput::make('current_value')
                                            ->label('Current Value')
                                            ->numeric()
                                            ->default(0)
                                            ->reactive()
                                            ->helperText('Where you are now'),

                                        Forms\Components\TextInput::make('target_value')
                                            ->label('Target Value')
                                            ->required()
                                            ->numeric()
                                            ->helperText('Your goal'),
                                    ]),
                            ])
                            ->addActionLabel('Add Key Result')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(5)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->columnSpanFull()
                            ->reorderable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Goal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Goal $record): string => Str::limit($record->description ?? '', 60)),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->color(fn (string $state): string => match ($state) {
                        'annual' => 'info',
                        'quarterly' => 'primary',
                        'monthly' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->icon(fn (Goal $record): string => $record->category_icon)
                    ->color(fn (Goal $record): string => $record->category_color)
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::title(str_replace('_', ' ', $state)) : 'Uncategorized'),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn (Goal $record): string => $record->progress_percentage . '%')
                    ->badge()
                    ->color(fn (Goal $record): string => match (true) {
                        $record->progress_percentage >= 75 => 'success',
                        $record->progress_percentage >= 50 => 'primary',
                        $record->progress_percentage >= 25 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn (Goal $record): string => $record->status_icon)
                    ->color(fn (Goal $record): string => $record->status_color)
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(fn (Goal $record): string => $record->days_remaining >= 0 ? $record->days_remaining . ' days left' : 'Overdue!'),

                Tables\Columns\IconColumn::make('last_check_in_at')
                    ->label('Check-in')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Goal $record) => $record->needsCheckIn() ? 'Needs weekly check-in' : 'Up to date')
                    ->getStateUsing(fn (Goal $record) => !$record->needsCheckIn()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'on_track' => 'On Track',
                        'in_progress' => 'In Progress',
                        'at_risk' => 'At Risk',
                        'off_track' => 'Off Track',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'revenue' => 'Revenue & Sales',
                        'customers' => 'Customers & Growth',
                        'product' => 'Product & Features',
                        'team' => 'Team & Hiring',
                        'operational' => 'Operations & Efficiency',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Action::make('check_in')
                    ->label('Check-in')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(fn (Goal $record) => $record->needsCheckIn())
                    ->url(fn (Goal $record) => GoalResource::getUrl('check-in', ['record' => $record])),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Goal $record) => GoalResource::getUrl('view', ['record' => $record])),

                EditAction::make()
                    ->color('gray'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No goals set yet')
            ->emptyStateDescription('Start achieving your business objectives by setting your first goal')
            ->emptyStateIcon('heroicon-o-flag')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Set Your First Goal')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoals::route('/'),
            'create' => Pages\CreateGoal::route('/create'),
            'view' => Pages\ViewGoal::route('/{record}'),
            'edit' => Pages\EditGoal::route('/{record}/edit'),
            'check-in' => Pages\CheckInGoal::route('/{record}/check-in'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->active()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $offTrack = static::getModel()::where('user_id', auth()->id())
            ->offTrack()
            ->count();

        return $offTrack > 0 ? 'danger' : 'success';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasFeature('goals_enabled') ?? false;
    }

}
