<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\InvoiceClientResource\Pages;
use App\Models\InvoiceClient;
use App\Services\CountriesService;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class InvoiceClientResource extends Resource
{
    protected static ?string $model = InvoiceClient::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clients';

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('company')
                            ->label('Company Name')
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Address')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Street Address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('state')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP/Postal Code')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Select::make('country')
                            ->options(CountriesService::getAllCountries())
                            ->searchable()
                            ->native(false)
                            ->default('US')
                            ->placeholder('Select a country')
                            ->helperText('Search by country name or select from the list'),
                    ])
                    ->collapsed(),

                Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Client')
                            ->default(true)
                            ->helperText('Inactive clients won\'t appear in invoice creation forms'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('country_name')
                    ->label('Country')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('invoice_count')
                    ->label('Invoices')
                    ->counts('invoices')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_owed')
                    ->label('Total Owed')
                    ->money('USD')
                    ->getStateUsing(fn (InvoiceClient $record) => $record->invoices()
                        ->whereIn('status', ['sent', 'partial', 'overdue'])
                        ->sum('balance_due'))
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'warning' : null),

                Tables\Columns\TextColumn::make('overdue_amount')
                    ->label('Overdue')
                    ->money('USD')
                    ->getStateUsing(fn (InvoiceClient $record) => $record->invoices()
                        ->where('status', 'overdue')
                        ->sum('balance_due'))
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All clients')
                    ->trueLabel('Active clients only')
                    ->falseLabel('Inactive clients only'),

                Tables\Filters\Filter::make('has_outstanding')
                    ->label('Has Outstanding Invoices')
                    ->query(fn (Builder $query) => $query->whereHas('invoices', function ($query) {
                        $query->whereIn('status', ['sent', 'partial', 'overdue']);
                    })),

                Tables\Filters\Filter::make('has_overdue')
                    ->label('Has Overdue Invoices')
                    ->query(fn (Builder $query) => $query->whereHas('invoices', function ($query) {
                        $query->where('status', 'overdue');
                    })),
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
            'index' => Pages\ListInvoiceClients::route('/'),
            'create' => Pages\CreateInvoiceClient::route('/create'),
            'edit' => Pages\EditInvoiceClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

}
