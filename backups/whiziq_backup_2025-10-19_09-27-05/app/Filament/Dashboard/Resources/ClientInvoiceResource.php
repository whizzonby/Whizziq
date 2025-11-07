<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;
use App\Models\ClientInvoice;
use App\Models\InvoiceClient;
use App\Services\InvoiceReminderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class ClientInvoiceResource extends Resource
{
    protected static ?string $model = ClientInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('invoice_client_id')
                                    ->label('Client')
                                    ->relationship('client', 'name', fn (Builder $query) => $query->where('user_id', auth()->id())->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('company')
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $data['user_id'] = auth()->id();
                                        return InvoiceClient::create($data)->id;
                                    }),

                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->default(fn () => ClientInvoice::generateInvoiceNumber())
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'paid' => 'Paid',
                                        'partial' => 'Partially Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->default(now()->addDays(30))
                                    ->required()
                                    ->after('invoice_date'),

                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'USD' => 'USD ($)',
                                        'EUR' => 'EUR (€)',
                                        'GBP' => 'GBP (£)',
                                        'CAD' => 'CAD ($)',
                                        'AUD' => 'AUD ($)',
                                    ])
                                    ->default('USD')
                                    ->native(false)
                                    ->required(),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Line Items')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                        $set('amount', $state * ($get('unit_price') ?? 0));
                                    }),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                        $set('amount', $state * ($get('quantity') ?? 1));
                                    }),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ])
                            ->columns(5)
                            ->reorderable('sort_order')
                            ->collapsible()
                            ->defaultItems(1)
                            ->addActionLabel('Add Item')
                            ->columnSpanFull(),
                    ]),

                Section::make('Totals')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = collect($items)->sum('amount');
                                        return '$' . number_format($subtotal, 2);
                                    }),

                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('Tax Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100),

                                Forms\Components\Placeholder::make('tax_amount_display')
                                    ->label('Tax Amount')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = collect($items)->sum('amount');
                                        $taxRate = $get('tax_rate') ?? 0;
                                        $taxAmount = ($subtotal * $taxRate) / 100;
                                        return '$' . number_format($taxAmount, 2);
                                    }),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Discount Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total Amount')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = collect($items)->sum('amount');
                                        $taxRate = $get('tax_rate') ?? 0;
                                        $taxAmount = ($subtotal * $taxRate) / 100;
                                        $discount = $get('discount_amount') ?? 0;
                                        $total = $subtotal + $taxAmount - $discount;
                                        return '$' . number_format($total, 2);
                                    })
                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('terms')
                            ->label('Payment Terms')
                            ->rows(3)
                            ->placeholder('Payment is due within 30 days...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('footer')
                            ->label('Footer')
                            ->rows(2)
                            ->placeholder('Thank you for your business!')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (ClientInvoice $record) => $record->is_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'sent',
                        'warning' => 'partial',
                        'danger' => 'overdue',
                        'success' => 'paid',
                        'secondary' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->formatStateUsing(fn (ClientInvoice $record) => $record->days_overdue > 0 ? $record->days_overdue : '-')
                    ->color('danger'),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'partial' => 'Partially Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('invoice_client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query) => $query->where('status', 'overdue')),

                Tables\Filters\Filter::make('due_soon')
                    ->label('Due in Next 7 Days')
                    ->query(fn (Builder $query) => $query->dueSoon(7)),
            ])
            ->actions([
                EditAction::make(),

                Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->visible(fn (ClientInvoice $record) => $record->canSendReminder())
                    ->requiresConfirmation()
                    ->action(function (ClientInvoice $record) {
                        $service = app(InvoiceReminderService::class);
                        $sent = $service->sendManualReminder($record);

                        if ($sent) {
                            Notification::make()
                                ->title('Reminder Sent')
                                ->success()
                                ->body("Payment reminder sent to {$record->client->name}")
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cannot Send Reminder')
                                ->warning()
                                ->body('Please wait 24 hours between reminders or check client email')
                                ->send();
                        }
                    }),

                Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (ClientInvoice $record) => !in_array($record->status, ['paid', 'cancelled']))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(fn (ClientInvoice $record) => $record->balance_due),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'paypal' => 'PayPal',
                                'stripe' => 'Stripe',
                                'other' => 'Other',
                            ])
                            ->default('other')
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction/Reference ID')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (ClientInvoice $record, array $data) {
                        $record->recordPayment(
                            amount: $data['amount'],
                            paymentMethod: $data['payment_method'],
                            transactionId: $data['transaction_id'] ?? null,
                            notes: $data['notes'] ?? null,
                            paymentDate: $data['payment_date']
                        );

                        Notification::make()
                            ->title('Payment Recorded')
                            ->success()
                            ->body("Payment of $" . number_format($data['amount'], 2) . " recorded successfully.")
                            ->send();
                    }),

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
            'index' => Pages\ListClientInvoices::route('/'),
            'create' => Pages\CreateClientInvoice::route('/create'),
            'edit' => Pages\EditClientInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = static::getModel()::where('user_id', auth()->id())
            ->where('status', 'overdue')
            ->count();

        return $overdueCount > 0 ? 'danger' : 'primary';
    }
}
