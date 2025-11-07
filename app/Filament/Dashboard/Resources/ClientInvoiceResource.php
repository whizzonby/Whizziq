<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ClientInvoiceResource\Pages;
use App\Models\ClientInvoice;
use App\Models\InvoiceClient;
use App\Services\InvoiceReminderService;
use App\Services\InvoicePDFService;
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
use Filament\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class ClientInvoiceResource extends Resource
{
    protected static ?string $model = ClientInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'All Invoices';

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->description('Configure your invoice information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('invoice_client_id')
                            ->label('Select Client')
                            ->relationship('client', 'name', fn (Builder $query) => $query->where('user_id', auth()->id())->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
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

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->default(fn () => ClientInvoice::generateInvoiceNumber())
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\Select::make('status')
                                    ->label('Invoice Status')
                                    ->options([
                                        'draft' => 'Saved',
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
                                    ->label('Currency')
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

                Section::make('Invoice Items')
                    ->description('Add products or services to this invoice')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Item / Service Description')
                                    ->required()
                                    ->placeholder('Enter product or service name')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('details')
                                    ->label('Additional Details (Optional)')
                                    ->rows(2)
                                    ->placeholder('Add item details, specifications, or notes...')
                                    ->columnSpanFull(),

                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                $set('amount', $state * ($get('unit_price') ?? 0));
                                            }),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price ($)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                $set('amount', $state * ($get('quantity') ?? 1));
                                            }),

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Line Total')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required()
                                            ->columnSpan(2)
                                            ->extraInputAttributes(['class' => 'text-lg font-semibold']),
                                    ]),
                            ])
                            ->reorderable('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Item')
                            ->collapsed(false)
                            ->defaultItems(1)
                            ->addActionLabel('+ Add Another Item')
                            ->deleteAction(
                                fn ($action) => $action->label('Remove')
                            )
                            ->cloneable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & Totals')
                    ->description('Configure tax, discounts, and view totals')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Left Column - Tax and Discount Inputs
                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->reactive()
                                            ->helperText('Enter tax percentage to apply'),

                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('Discount Amount ($)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->minValue(0)
                                            ->reactive()
                                            ->helperText('Enter fixed discount amount'),
                                    ]),

                                // Right Column - Totals Display
                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('subtotal_display')
                                            ->label('Subtotal')
                                            ->content(function (Get $get) {
                                                $items = $get('items') ?? [];
                                                $subtotal = collect($items)->sum('amount');
                                                return '$' . number_format($subtotal, 2);
                                            })
                                            ->extraAttributes(['class' => 'text-lg']),

                                        Forms\Components\Placeholder::make('tax_amount_display')
                                            ->label('Tax Amount')
                                            ->content(function (Get $get) {
                                                $items = $get('items') ?? [];
                                                $subtotal = collect($items)->sum('amount');
                                                $taxRate = $get('tax_rate') ?? 0;
                                                $taxAmount = ($subtotal * $taxRate) / 100;
                                                return '$' . number_format($taxAmount, 2);
                                            })
                                            ->extraAttributes(['class' => 'text-lg']),

                                        Forms\Components\Placeholder::make('discount_display')
                                            ->label('Discount Applied')
                                            ->content(function (Get $get) {
                                                $discount = $get('discount_amount') ?? 0;
                                                return '- $' . number_format($discount, 2);
                                            })
                                            ->extraAttributes(['class' => 'text-lg']),

                                        Forms\Components\Placeholder::make('divider')
                                            ->label('')
                                            ->content('─────────────────────'),

                                        Forms\Components\Placeholder::make('total_display')
                                            ->label('TOTAL AMOUNT DUE')
                                            ->content(function (Get $get) {
                                                $items = $get('items') ?? [];
                                                $subtotal = collect($items)->sum('amount');
                                                $taxRate = $get('tax_rate') ?? 0;
                                                $taxAmount = ($subtotal * $taxRate) / 100;
                                                $discount = $get('discount_amount') ?? 0;
                                                $total = $subtotal + $taxAmount - $discount;
                                                return '$' . number_format($total, 2);
                                            })
                                            ->extraAttributes(['class' => 'font-bold text-2xl text-primary-600']),
                                    ]),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Add notes, terms, and footer text (optional)')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Invoice Notes')
                            ->rows(3)
                            ->placeholder('Add any special notes or instructions for the client...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('terms')
                            ->label('Payment Terms & Conditions')
                            ->rows(3)
                            ->placeholder('Example: Payment is due within 30 days. Late payments may incur additional fees.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('footer')
                            ->label('Invoice Footer Text')
                            ->rows(2)
                            ->placeholder('Thank you for your business!')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->columns(1),
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
                    ->formatStateUsing(function (string $state): string {
                        // Normalize any accidental "send" value to "sent"
                        if ($state === 'send') {
                            return 'sent';
                        }

                        // Display label "Saved" for internal draft
                        if ($state === 'draft') {
                            return 'Saved';
                        }

                        return ucfirst($state);
                    })
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
                        'draft' => 'Saved',
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
                            paymentDate: \Carbon\Carbon::parse($data['payment_date'])
                        );

                        Notification::make()
                            ->title('Payment Recorded')
                            ->success()
                            ->body("Payment of $" . number_format($data['amount'], 2) . " recorded successfully.")
                            ->send();
                    }),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (ClientInvoice $record) => route('invoice.download-pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('email_invoice')
                    ->label('Email to Client')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->visible(fn (ClientInvoice $record) => !empty($record->client->email))
                    ->form([
                        Forms\Components\TextInput::make('recipient_email')
                            ->label('Recipient Email')
                            ->email()
                            ->required()
                            ->default(fn (ClientInvoice $record) => $record->client->email),

                        Forms\Components\Textarea::make('custom_message')
                            ->label('Custom Message (Optional)')
                            ->rows(4)
                            ->placeholder('Add a personal message to include in the email...'),
                    ])
                    ->action(function (ClientInvoice $record, array $data) {
                        $pdfService = app(InvoicePDFService::class);

                        // Temporarily update client email if different
                        $originalEmail = $record->client->email;
                        if ($data['recipient_email'] !== $originalEmail) {
                            $record->client->update(['email' => $data['recipient_email']]);
                        }

                        $sent = $pdfService->emailToClient(
                            $record,
                            $data['custom_message'] ?? null
                        );

                        // Restore original email if it was changed
                        if ($data['recipient_email'] !== $originalEmail) {
                            $record->client->update(['email' => $originalEmail]);
                        }

                        if ($sent) {
                            Notification::make()
                                ->title('Invoice Emailed')
                                ->success()
                                ->body("Invoice {$record->invoice_number} sent to {$data['recipient_email']}")
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Email Failed')
                                ->danger()
                                ->body('Failed to send invoice email. Please check your email configuration.')
                                ->send();
                        }
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_email_invoices')
                        ->label('Email to Clients')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->form([
                            Forms\Components\Textarea::make('custom_message')
                                ->label('Custom Message (Optional)')
                                ->rows(4)
                                ->placeholder('Add a personal message to include in all emails...'),
                        ])
                        ->action(function ($records, array $data) {
                            $pdfService = app(InvoicePDFService::class);
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $record) {
                                if (empty($record->client->email)) {
                                    $failCount++;
                                    continue;
                                }

                                $sent = $pdfService->emailToClient(
                                    $record,
                                    $data['custom_message'] ?? null
                                );

                                if ($sent) {
                                    $successCount++;
                                } else {
                                    $failCount++;
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Invoices Emailed')
                                    ->success()
                                    ->body("Successfully sent {$successCount} invoice(s). Failed: {$failCount}")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Email Failed')
                                    ->danger()
                                    ->body('Failed to send invoice emails. Please check email configuration.')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_generate_pdfs')
                        ->label('Generate PDFs')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $pdfService = app(InvoicePDFService::class);
                            $invoiceIds = $records->pluck('id')->toArray();
                            $count = $pdfService->bulkGenerate($invoiceIds);

                            Notification::make()
                                ->title('PDFs Generated')
                                ->success()
                                ->body("Successfully generated {$count} invoice PDF(s)")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientInvoices::route('/'),
            // 'create' => Pages\CreateClientInvoice::route('/create'), // Removed - use Invoice Builder instead
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

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreate(\App\Models\ClientInvoice::class, 'finance_invoices_limit') ?? false;
    }

}
