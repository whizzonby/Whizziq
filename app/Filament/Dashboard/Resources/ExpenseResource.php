<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Components\Section;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Expenses';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics Data';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
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
                        Forms\Components\TextInput::make('category')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Office Rent, Software Subscriptions, Client Meals, etc.')
                            ->helperText('Enter your expense category - AI can suggest based on description')
                            ->datalist([
                                'Salaries & Wages',
                                'Rent & Lease',
                                'Advertising & Marketing',
                                'Office Supplies',
                                'Utilities',
                                'Insurance',
                                'Travel & Transportation',
                                'Maintenance & Repairs',
                                'Software & Subscriptions',
                                'Professional Services',
                                'Equipment & Tools',
                                'Meals & Entertainment',
                                'Training & Education',
                                'Telecommunications',
                                'Banking & Fees',
                                'Inventory & Stock',
                                'Shipping & Delivery',
                                'Other',
                            ])
                            ->live()
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500)
                            ->live(debounce: 1000)
                            ->helperText('Describe the expense for AI category suggestion')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Tax Information')
                    ->schema([
                        Forms\Components\Toggle::make('is_tax_deductible')
                            ->label('Tax Deductible')
                            ->helperText('Mark if this expense is tax deductible')
                            ->live()
                            ->columnSpan(1),
                        Forms\Components\Select::make('tax_category_id')
                            ->relationship('taxCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Tax Category')
                            ->visible(fn ($get) => $get('is_tax_deductible'))
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('deductible_amount')
                            ->numeric()
                            ->prefix('$')
                            ->label('Deductible Amount')
                            ->helperText('Leave blank to deduct full amount')
                            ->visible(fn ($get) => $get('is_tax_deductible'))
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('tax_notes')
                            ->rows(2)
                            ->label('Tax Notes')
                            ->visible(fn ($get) => $get('is_tax_deductible'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([])
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'salary') || str_contains(strtolower($state), 'wage') => 'danger',
                        str_contains(strtolower($state), 'rent') || str_contains(strtolower($state), 'lease') => 'warning',
                        str_contains(strtolower($state), 'advertising') || str_contains(strtolower($state), 'marketing') => 'info',
                        str_contains(strtolower($state), 'software') || str_contains(strtolower($state), 'subscription') => 'success',
                        str_contains(strtolower($state), 'professional') || str_contains(strtolower($state), 'service') => 'primary',
                        str_contains(strtolower($state), 'travel') || str_contains(strtolower($state), 'transportation') => 'cyan',
                        str_contains(strtolower($state), 'meal') || str_contains(strtolower($state), 'entertainment') => 'purple',
                        str_contains(strtolower($state), 'utility') || str_contains(strtolower($state), 'utilities') => 'orange',
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
                            ->label('Total'),
                    ]),
                Tables\Columns\IconColumn::make('is_tax_deductible')
                    ->label('Tax Ded.')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->is_tax_deductible
                        ? 'Deductible: $' . number_format($record->calculateDeductibleAmount(), 2)
                        : 'Not deductible'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('taxCategory.name')
                    ->label('Tax Category')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(function () {
                        return Expense::where('user_id', auth()->id())
                            ->distinct()
                            ->pluck('category', 'category')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->label('Filter by Category'),
                Tables\Filters\TernaryFilter::make('is_tax_deductible')
                    ->label('Tax Deductible')
                    ->placeholder('All expenses')
                    ->trueLabel('Deductible only')
                    ->falseLabel('Non-deductible only'),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
