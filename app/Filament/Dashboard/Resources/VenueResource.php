<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\VenueResource\Pages;
use App\Models\Venue;
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

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Venues';

    protected static UnitEnum|string|null $navigationGroup = 'Booking';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Venue name and description')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Main Office, Conference Room A, etc.'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active venues are available for booking'),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Capacity')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Maximum simultaneous appointments at this venue')
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->description('Physical location address')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Forms\Components\TextInput::make('address_line_1')
                            ->label('Address Line 1')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address_line_2')
                            ->label('Address Line 2')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('zip')
                            ->label('ZIP/Postal Code')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country_code')
                            ->label('Country Code')
                            ->maxLength(2)
                            ->placeholder('US'),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->helperText('For Google Maps integration'),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->helperText('For Google Maps integration'),
                    ])
                    ->columns(2),

                Section::make('Contact Information')
                    ->description('Venue-specific contact details')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Additional Information')
                    ->description('Parking, directions, and access instructions')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Textarea::make('parking_info')
                            ->label('Parking Information')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('public_transport_info')
                            ->label('Public Transport Information')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('access_instructions')
                            ->label('Access Instructions')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Building access, security codes, etc.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('directions')
                            ->label('Directions')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Custom directions to help clients find the venue')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('full_address')
                    ->label('Address')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->counts('appointments')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All venues')
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
            ->emptyStateHeading('No venues yet')
            ->emptyStateDescription('Create your first venue to enable in-person appointments')
            ->emptyStateIcon('heroicon-o-map-pin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}


