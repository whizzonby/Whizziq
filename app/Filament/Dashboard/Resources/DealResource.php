<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\DealResource\Pages;
use App\Models\Deal;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Deals';

    protected static UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Deal Information')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\Select::make('contact_id')
                            ->label('Contact')
                            ->relationship('contact', 'name', fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., $50K Website Redesign'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pipeline & Value')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('stage')
                                    ->options([
                                        'lead' => 'Lead',
                                        'qualified' => 'Qualified',
                                        'proposal' => 'Proposal',
                                        'negotiation' => 'Negotiation',
                                        'won' => 'Won',
                                        'lost' => 'Lost',
                                    ])
                                    ->default('lead')
                                    ->native(false)
                                    ->required(),

                                Forms\Components\TextInput::make('value')
                                    ->label('Deal Value')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->default(0),

                                Forms\Components\TextInput::make('probability')
                                    ->label('Win Probability (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(50)
                                    ->helperText('Auto-adjusts when stage changes'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'low' => 'Low',
                                        'medium' => 'Medium',
                                        'high' => 'High',
                                    ])
                                    ->default('medium')
                                    ->native(false)
                                    ->required(),

                                Forms\Components\DatePicker::make('expected_close_date')
                                    ->label('Expected Close Date')
                                    ->default(now()->addDays(30))
                                    ->required(),

                                Forms\Components\Select::make('currency')
                                    ->options(\App\Models\Currency::getSelectOptions())
                                    ->searchable()
                                    ->default('USD')
                                    ->native(false),
                            ]),
                    ]),

                Section::make('Products & Line Items')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Add products/services included in this deal')
                    ->schema([
                        Forms\Components\Repeater::make('products')
                            ->relationship('products')
                            ->schema([
                                // Row 1 — mirror the 3-column grid style used in "Pipeline & Value"
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('product_name')
                                            ->label('Product/Service')
                                            ->required()
                                            ->placeholder('e.g., Website Design'),

                                        Forms\Components\TextInput::make('description')
                                            ->label('Description')
                                            ->placeholder('Optional details'),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->default(0)
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set, $get) =>
                                                $set('line_total', ($get('quantity') ?? 1) * ($get('unit_price') ?? 0))
                                            ),
                                    ]),

                                // Row 2 — quantities and calculated totals in a clean row
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qty')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set, $get) =>
                                                $set('line_total', ($get('quantity') ?? 1) * ($get('unit_price') ?? 0))
                                            ),

                                        Forms\Components\TextInput::make('discount_percent')
                                            ->label('Disc %')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->helperText('Discount percentage'),

                                        Forms\Components\TextInput::make('tax_percent')
                                            ->label('Tax %')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->helperText('Tax percentage'),

                                        Forms\Components\TextInput::make('line_total')
                                            ->label('Line Total')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Auto-calculated'),
                                    ]),
                            ])
                            ->reorderable('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                            ->addActionLabel('Add Product/Service')
                            ->defaultItems(0)
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Calculate total from all products
                                $total = collect($state)->sum('line_total');
                                $set('value', $total);
                            }),
                    ])
                    ->collapsed(),

                Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('source')
                            ->label('Lead Source')
                            ->maxLength(255)
                            ->placeholder('e.g., referral, inbound, event'),

                        Forms\Components\TextInput::make('loss_reason')
                            ->label('Loss Reason')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('stage') === 'lost'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('stage')
                    ->colors([
                        'secondary' => 'lead',
                        'primary' => 'qualified',
                        'warning' => 'proposal',
                        'info' => 'negotiation',
                        'success' => 'won',
                        'danger' => 'lost',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('weighted_value')
                    ->label('Weighted Value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->description('Value × Probability'),

                Tables\Columns\TextColumn::make('probability')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'high',
                        'warning' => 'medium',
                        'secondary' => 'low',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expected_close_date')
                    ->label('Expected Close')
                    ->date()
                    ->sortable()
                    ->color(fn ($state, $record) => !$record->is_closed && $state && $state->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('days_in_stage')
                    ->label('Days in Stage')
                    ->formatStateUsing(fn (Deal $record): string => 
                        $record->days_in_stage . ' day' . ($record->days_in_stage !== 1 ? 's' : '')
                    )
                    ->color(fn (Deal $record): string => $record->days_in_stage_color)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('updated_at', $direction === 'asc' ? 'desc' : 'asc');
                    })
                    ->toggleable()
                    ->tooltip(fn (Deal $record): ?string => 
                        $record->isStuckInStage(30) 
                            ? "This deal has been in the {$record->stage_label} stage for {$record->days_in_stage} days. Consider moving it forward."
                            : null
                    ),
            ])
            ->defaultSort('expected_close_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'lead' => 'Lead',
                        'qualified' => 'Qualified',
                        'proposal' => 'Proposal',
                        'negotiation' => 'Negotiation',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                Tables\Filters\Filter::make('open')
                    ->label('Open Deals')
                    ->query(fn (Builder $query) => $query->open()),

                Tables\Filters\Filter::make('closing_soon')
                    ->label('Closing in 30 Days')
                    ->query(fn (Builder $query) => $query->closingSoon(30)),
            ])
            ->actions([
                EditAction::make(),

                Action::make('move_stage')
                    ->label('Move Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('stage')
                            ->options([
                                'lead' => 'Lead',
                                'qualified' => 'Qualified',
                                'proposal' => 'Proposal',
                                'negotiation' => 'Negotiation',
                                'won' => 'Won',
                                'lost' => 'Lost',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('loss_reason')
                            ->label('Loss Reason')
                            ->visible(fn ($get) => $get('stage') === 'lost')
                            ->requiredWith('stage'),
                    ])
                    ->action(function (Deal $record, array $data) {
                        if ($data['stage'] === 'lost' && isset($data['loss_reason'])) {
                            $record->markAsLost($data['loss_reason']);
                        } elseif ($data['stage'] === 'won') {
                            $record->markAsWon();
                        } else {
                            $record->moveToStage($data['stage']);
                        }

                        Notification::make()
                            ->title('Deal Stage Updated')
                            ->success()
                            ->body("Deal moved to {$data['stage']}")
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->open()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreate(Deal::class, 'crm_deals_limit') ?? false;
    }

}
