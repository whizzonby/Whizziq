<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ContactSegmentResource\Pages;
use App\Models\ContactSegment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;

class ContactSegmentResource extends Resource
{
    protected static ?string $model = ContactSegment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Contact Segments';

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Segment Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Segment Name')
                            ->placeholder('e.g., High-Value Clients'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Describe this segment...'),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Color')
                                    ->default('#3b82f6'),

                                Forms\Components\Toggle::make('is_favorite')
                                    ->label('Mark as Favorite')
                                    ->helperText('Favorite segments appear at the top'),
                            ]),
                    ]),

                Section::make('Segment Filters')
                    ->description('Define the criteria for contacts in this segment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('filters.type')
                                    ->label('Contact Type')
                                    ->options([
                                        'client' => 'Client',
                                        'lead' => 'Lead',
                                        'partner' => 'Partner',
                                        'investor' => 'Investor',
                                        'vendor' => 'Vendor',
                                    ])
                                    ->placeholder('Any type'),

                                Forms\Components\Select::make('filters.status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'archived' => 'Archived',
                                    ])
                                    ->placeholder('Any status'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('filters.priority')
                                    ->label('Priority')
                                    ->options([
                                        'low' => 'Low',
                                        'medium' => 'Medium',
                                        'high' => 'High',
                                        'vip' => 'VIP',
                                    ])
                                    ->placeholder('Any priority'),

                                Forms\Components\Select::make('filters.relationship_strength')
                                    ->label('Relationship Strength')
                                    ->options([
                                        'hot' => 'Hot',
                                        'warm' => 'Warm',
                                        'cold' => 'Cold',
                                    ])
                                    ->placeholder('Any strength'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('filters.min_lifetime_value')
                                    ->label('Min Lifetime Value ($)')
                                    ->numeric()
                                    ->placeholder('0'),

                                Forms\Components\TextInput::make('filters.max_lifetime_value')
                                    ->label('Max Lifetime Value ($)')
                                    ->numeric()
                                    ->placeholder('No limit'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('filters.has_deals')
                                    ->label('Has Deals')
                                    ->helperText('Only contacts with deals'),

                                Forms\Components\Toggle::make('filters.needs_follow_up')
                                    ->label('Needs Follow-Up')
                                    ->helperText('Contacts requiring follow-up'),

                                Forms\Components\TextInput::make('filters.last_contact_days')
                                    ->label('Last Contact (days ago)')
                                    ->numeric()
                                    ->placeholder('e.g., 30')
                                    ->helperText('Not contacted in X days'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('filters.source')
                                    ->label('Source')
                                    ->placeholder('e.g., Website, Referral'),

                                Forms\Components\TextInput::make('filters.tags')
                                    ->label('Tags (contains)')
                                    ->placeholder('e.g., VIP, Enterprise'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ContactSegment $record): string => $record->description ?? ''),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),

                Tables\Columns\TextColumn::make('contact_count')
                    ->label('Contacts')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favorites Only')
                    ->placeholder('All')
                    ->trueLabel('Favorites')
                    ->falseLabel('Non-favorites'),
            ])
            ->actions([
                EditAction::make(),

                Action::make('refresh_count')
                    ->label('Refresh Count')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ContactSegment $record) {
                        $record->updateContactCount();
                        \Filament\Notifications\Notification::make()
                            ->title('Count Updated')
                            ->success()
                            ->body("Segment now has {$record->contact_count} contacts")
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
            'index' => Pages\ListContactSegments::route('/'),
            'create' => Pages\CreateContactSegment::route('/create'),
            'edit' => Pages\EditContactSegment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasFeature('crm_segments') ?? false;
    }

}
