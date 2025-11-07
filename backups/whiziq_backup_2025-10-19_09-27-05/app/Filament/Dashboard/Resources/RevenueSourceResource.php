<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\RevenueSourceResource\Pages;
use App\Models\RevenueSource;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class RevenueSourceResource extends Resource
{
    protected static ?string $model = RevenueSource::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Revenue Sources';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Revenue Source Information')
                    ->description('Track revenue from different sources')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('source')
                    ->required()
                    ->options([
                        'online_sales' => 'Online Sales',
                        'custom_orders' => 'Custom Orders',
                        'subscriptions' => 'Subscriptions',
                        'consulting' => 'Consulting',
                        'licensing' => 'Licensing',
                        'partnerships' => 'Partnerships',
                        'other' => 'Other',
                    ])
                    ->native(false)
                    ->searchable(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->step(0.01),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->helperText('Percentage of total revenue'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'online_sales' => 'Online Sales',
                        'custom_orders' => 'Custom Orders',
                        'subscriptions' => 'Subscriptions',
                        'consulting' => 'Consulting',
                        'licensing' => 'Licensing',
                        'partnerships' => 'Partnerships',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevenueSources::route('/'),
            'create' => Pages\CreateRevenueSource::route('/create'),
            'edit' => Pages\EditRevenueSource::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
