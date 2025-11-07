<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AppointmentTypeResource\Pages;
use App\Models\AppointmentType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class AppointmentTypeResource extends Resource
{
    protected static ?string $model = AppointmentType::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Appointment Types';

    protected static UnitEnum|string|null $navigationGroup = 'Booking';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('30-min Consultation'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->minValue(15)
                            ->maxValue(480),

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\ColorPicker::make('color')
                            ->default('#3B82F6'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active types are shown on your booking page'),
                    ])
                    ->columns(2),

                Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\TextInput::make('buffer_before_minutes')
                            ->label('Buffer Before (minutes)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Prep time before appointment'),

                        Forms\Components\TextInput::make('buffer_after_minutes')
                            ->label('Buffer After (minutes)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Cleanup time after appointment'),

                        Forms\Components\TextInput::make('max_per_day')
                            ->label('Max Per Day')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Booking Form Requirements')
                    ->schema([
                        Forms\Components\Toggle::make('require_phone')
                            ->label('Require Phone Number')
                            ->default(false),

                        Forms\Components\Toggle::make('require_company')
                            ->label('Require Company Name')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('')
                    ->width(10),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state . ' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Bookings')
                    ->counts('appointments')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All types')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No appointment types yet')
            ->emptyStateDescription('Create your first service offering')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppointmentTypes::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
