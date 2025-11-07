<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\BusinessMetricResource\Pages;
use App\Models\BusinessMetric;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class BusinessMetricResource extends Resource
{
    protected static ?string $model = BusinessMetric::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Business Metrics';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business Metrics')
                    ->description('Financial performance data')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('revenue')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Forms\Components\TextInput::make('profit')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Forms\Components\TextInput::make('expenses')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Forms\Components\TextInput::make('cash_flow')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('revenue_change_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01),
                        Forms\Components\TextInput::make('profit_change_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01),
                        Forms\Components\TextInput::make('expenses_change_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01),
                        Forms\Components\TextInput::make('cash_flow_change_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01),
                    ]),
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
                Tables\Columns\TextColumn::make('revenue')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expenses')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cash_flow')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessMetrics::route('/'),
            'create' => Pages\CreateBusinessMetric::route('/create'),
            'edit' => Pages\EditBusinessMetric::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
