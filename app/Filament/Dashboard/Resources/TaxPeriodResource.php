<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\TaxPeriodResource\Pages;
use App\Models\TaxPeriod;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaxPeriodResource extends Resource
{
    protected static ?string $model = TaxPeriod::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tax Periods';
    protected static UnitEnum|string|null $navigationGroup = 'Tax & Compliance';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Q1 2024 or FY 2024'),

                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                    ])
                    ->default('quarterly'),

                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->label('Period Start Date'),

                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->label('Period End Date')
                    ->after('start_date'),

                Forms\Components\DatePicker::make('filing_deadline')
                    ->label('Filing Deadline')
                    ->helperText('When do you need to file taxes for this period?'),

                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'Active',
                        'closed' => 'Closed',
                        'filed' => 'Filed',
                    ])
                    ->default('active'),

                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
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

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'quarterly' => 'info',
                        'annual' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('period_range')
                    ->label('Period')
                    ->formatStateUsing(fn (TaxPeriod $record): string =>
                        $record->start_date->format('M d, Y') . ' - ' . $record->end_date->format('M d, Y')
                    ),

                Tables\Columns\TextColumn::make('filing_deadline')
                    ->label('Filing Deadline')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(fn (TaxPeriod $record): ?string => $record->filing_deadline ?
                        ($record->filing_deadline->isPast() && $record->status !== 'filed' ? '⚠️ Overdue' :
                        ($record->filing_deadline->diffInDays(now()) <= 7 ? '🔥 Due Soon' : null)) : null
                    )
                    ->color(fn (TaxPeriod $record): string =>
                        $record->filing_deadline && $record->filing_deadline->isPast() && $record->status !== 'filed' ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'warning',
                        'filed' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'closed' => 'Closed',
                        'filed' => 'Filed',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'quarterly' => 'Quarterly',
                        'annual' => 'Annual',
                    ]),
            ])
            ->actions([
                Action::make('close')
                    ->label('Close Period')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (TaxPeriod $record) => $record->isActive())
                    ->action(fn (TaxPeriod $record) => $record->close())
                    ->requiresConfirmation(),

                Action::make('file')
                    ->label('Mark as Filed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TaxPeriod $record) => !$record->isFiled())
                    ->action(fn (TaxPeriod $record) => $record->markAsFiled())
                    ->requiresConfirmation(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('filing_deadline', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxPeriods::route('/'),
            'create' => Pages\CreateTaxPeriod::route('/create'),
            'edit' => Pages\EditTaxPeriod::route('/{record}/edit'),
        ];
    }

}
