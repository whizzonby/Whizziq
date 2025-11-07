<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\SwotAnalysisResource\Pages;
use App\Models\SwotAnalysis;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class SwotAnalysisResource extends Resource
{
    protected static ?string $model = SwotAnalysis::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'SWOT Analysis';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'strength' => 'Strength',
                        'weakness' => 'Weakness',
                        'opportunity' => 'Opportunity',
                        'threat' => 'Threat',
                    ])
                    ->native(false),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
                Forms\Components\TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10)
                    ->helperText('Priority from 1-10 (higher is more important)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'strength' => 'success',
                        'weakness' => 'danger',
                        'opportunity' => 'info',
                        'threat' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'strength' => 'Strength',
                        'weakness' => 'Weakness',
                        'opportunity' => 'Opportunity',
                        'threat' => 'Threat',
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
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSwotAnalyses::route('/'),
            'create' => Pages\CreateSwotAnalysis::route('/create'),
            'edit' => Pages\EditSwotAnalysis::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
