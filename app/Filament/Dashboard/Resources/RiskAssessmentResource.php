<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\RiskAssessmentResource\Pages;
use App\Models\RiskAssessment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use UnitEnum;
use BackedEnum;

class RiskAssessmentResource extends Resource
{
    protected static ?string $model = RiskAssessment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Risk Assessments';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Risk Assessment')
                    ->description('Evaluate business risks')
                    ->icon('heroicon-o-shield-exclamation')
                    ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('risk_score')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->label('Risk Score (0-100)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $level = match (true) {
                                    $state < 25 => 'low',
                                    $state < 50 => 'moderate',
                                    $state < 75 => 'high',
                                    default => 'critical',
                                };
                                $set('risk_level', $level);
                            }),
                        Forms\Components\Select::make('risk_level')
                            ->required()
                            ->options([
                                'low' => 'Low',
                                'moderate' => 'Moderate',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->native(false)
                            ->label('Risk Level'),
                    ]),
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('loan_worthiness')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(0)
                            ->label('Loan Worthiness (0-100)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $level = match (true) {
                                    $state < 40 => 'poor',
                                    $state < 60 => 'fair',
                                    $state < 80 => 'good',
                                    default => 'excellent',
                                };
                                $set('loan_worthiness_level', $level);
                            }),
                        Forms\Components\Select::make('loan_worthiness_level')
                            ->required()
                            ->options([
                                'poor' => 'Poor',
                                'fair' => 'Fair',
                                'good' => 'Good',
                                'excellent' => 'Excellent',
                            ])
                            ->native(false)
                            ->label('Loan Worthiness Level'),
                    ]),
                Forms\Components\TagsInput::make('risk_factors')
                    ->label('Risk Factors')
                    ->placeholder('Add risk factor')
                    ->helperText('Press Enter after each risk factor'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state < 25 => 'success',
                        $state < 50 => 'warning',
                        $state < 75 => 'danger',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'moderate' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_worthiness')
                    ->label('Loan Worthiness')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_worthiness_level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'poor' => 'danger',
                        'fair' => 'warning',
                        'good' => 'success',
                        'excellent' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_level')
                    ->options([
                        'low' => 'Low',
                        'moderate' => 'Moderate',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\SelectFilter::make('loan_worthiness_level')
                    ->label('Loan Worthiness')
                    ->options([
                        'poor' => 'Poor',
                        'fair' => 'Fair',
                        'good' => 'Good',
                        'excellent' => 'Excellent',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
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
            'index' => Pages\ListRiskAssessments::route('/'),
            'create' => Pages\CreateRiskAssessment::route('/create'),
            'view' => Pages\ViewRiskAssessment::route('/{record}'),
            'edit' => Pages\EditRiskAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
