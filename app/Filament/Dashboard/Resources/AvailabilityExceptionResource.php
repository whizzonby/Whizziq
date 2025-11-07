<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AvailabilityExceptionResource\Pages;
use App\Models\AvailabilityException;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityExceptionResource extends Resource
{
    protected static ?string $model = AvailabilityException::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationLabel = 'Time Off / Exceptions';

    public static function getNavigationGroup(): ?string
    {
        return 'Booking';
    }

    //protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exception Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Summer Vacation'),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->placeholder('Optional notes'),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('exception_type')
                                    ->label('Type')
                                    ->options([
                                        'vacation' => 'Vacation',
                                        'holiday' => 'Holiday',
                                        'sick_leave' => 'Sick Leave',
                                        'personal' => 'Personal Time',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\DateTimePicker::make('start_date')
                                    ->required()
                                    ->native(false),

                                Forms\Components\DateTimePicker::make('end_date')
                                    ->required()
                                    ->native(false)
                                    ->after('start_date'),
                            ]),

                        Forms\Components\Toggle::make('is_all_day')
                            ->label('All Day Exception')
                            ->helperText('Block entire day(s) regardless of specific times'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('exception_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vacation' => 'primary',
                        'holiday' => 'success',
                        'sick_leave' => 'danger',
                        'personal' => 'warning',
                        'other' => 'secondary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Starts')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ends')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_all_day')
                    ->label('All Day')
                    ->boolean(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('exception_type')
                    ->options([
                        'vacation' => 'Vacation',
                        'holiday' => 'Holiday',
                        'sick_leave' => 'Sick Leave',
                        'personal' => 'Personal Time',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListAvailabilityExceptions::route('/'),
            'create' => Pages\CreateAvailabilityException::route('/create'),
            'edit' => Pages\EditAvailabilityException::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
