<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\MarketingMetricResource\Pages;
use App\Models\MarketingMetric;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class MarketingMetricResource extends Resource
{
    protected static ?string $model = MarketingMetric::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Marketing Metrics';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Channel and campaign details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),
                        Forms\Components\Select::make('platform')
                            ->required()
                            ->options([
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'linkedin' => 'LinkedIn',
                                'twitter' => 'Twitter',
                                'youtube' => 'YouTube',
                                'tiktok' => 'TikTok',
                            ])
                            ->native(false)
                            ->searchable()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('followers')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('engagement')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Total likes, comments, shares, etc.')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('reach')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Total people reached')
                            ->columnSpan(1),
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
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'facebook' => 'info',
                        'instagram' => 'danger',
                        'linkedin' => 'primary',
                        'twitter' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('followers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('engagement')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reach')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'linkedin' => 'LinkedIn',
                        'twitter' => 'Twitter',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
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
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingMetrics::route('/'),
            'create' => Pages\CreateMarketingMetric::route('/create'),
            'edit' => Pages\EditMarketingMetric::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
