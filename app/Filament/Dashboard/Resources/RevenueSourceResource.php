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
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
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
                Section::make('Revenue Details')
                    ->description('Track revenue from different sources and channels')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('source')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Product Sales, Consulting, Freelance Work, Rentals, etc.')
                            ->helperText('Enter your revenue source name - be as specific as you need')
                            ->datalist([
                                'Product Sales',
                                'Service Fees',
                                'Consulting Services',
                                'Subscriptions (MRR)',
                                'Freelance Work',
                                'Licensing Fees',
                                'Commissions',
                                'Affiliate Income',
                                'Advertising Revenue',
                                'Partnership Revenue',
                                'Rental Income',
                                'Online Sales',
                                'Custom Orders',
                                'Other',
                            ])
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('percentage')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Percentage of total revenue (optional)')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Additional details about this revenue...')
                            ->columnSpanFull(),
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'subscription') => 'success',
                        str_contains(strtolower($state), 'sales') || str_contains(strtolower($state), 'product') => 'info',
                        str_contains(strtolower($state), 'consulting') || str_contains(strtolower($state), 'service') => 'primary',
                        str_contains(strtolower($state), 'licensing') || str_contains(strtolower($state), 'license') => 'warning',
                        str_contains(strtolower($state), 'partnership') || str_contains(strtolower($state), 'affiliate') => 'purple',
                        str_contains(strtolower($state), 'rental') || str_contains(strtolower($state), 'rent') => 'cyan',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD')
                            ->label('Total Revenue'),
                        Tables\Columns\Summarizers\Average::make()
                            ->money('USD')
                            ->label('Average'),
                    ]),
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Percentage of total revenue'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(function () {
                        return RevenueSource::where('user_id', auth()->id())
                            ->distinct()
                            ->pluck('source', 'source')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->label('Filter by Revenue Source'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Tables\Filters\Filter::make('high_value')
                    ->label('High Value (>$1000)')
                    ->query(fn ($query) => $query->where('amount', '>', 1000))
                    ->toggle(),
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
