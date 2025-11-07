<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\ClientInvoice;
use App\Models\InvoiceClient;
use App\Models\ClientInvoiceItem;
use App\Services\InvoicePDFService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class InvoiceBuilderPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-plus';

    protected string $view = 'filament.dashboard.pages.invoice-builder-page';

    protected static ?string $navigationLabel = 'Invoice Builder';

    protected static ?string $title = 'Beautiful Invoice Builder';

    public function getTitle(): string
    {
        return $this->invoiceId ? 'Edit Invoice' : 'Beautiful Invoice Builder';
    }

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public string $template = 'modern';

    public string $primaryColor = '#3b82f6';

    public string $accentColor = '#10b981';

    public ?int $invoiceId = null;

    public function mount(): void
    {
        try {
            // Check if we're editing an existing invoice
            $invoiceId = request()->query('invoice');

            if ($invoiceId) {
                $invoice = ClientInvoice::with(['client', 'items'])
                    ->where('user_id', auth()->id())
                    ->findOrFail($invoiceId);

                $this->invoiceId = $invoice->id;

                // Load invoice data into form
                $this->form->fill([
                    'invoice_client_id' => $invoice->invoice_client_id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'currency' => $invoice->currency,
                    'tax_rate' => $invoice->tax_rate,
                    'discount_amount' => $invoice->discount_amount,
                    'notes' => $invoice->notes,
                    'terms' => $invoice->terms,
                    'footer' => $invoice->footer,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'description' => $item->description,
                            'details' => $item->details,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'amount' => $item->amount,
                        ];
                    })->toArray(),
                ]);
            } else {
                // New invoice - default values
                $this->form->fill([
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(30),
                    'currency' => 'USD',
                    'tax_rate' => 0,
                    'discount_amount' => 0,
                    'items' => [
                        [
                            'description' => '',
                            'quantity' => 1,
                            'unit_price' => 0,
                            'amount' => 0,
                        ]
                    ],
                ]);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Notification::make()
                ->title('Invoice Not Found')
                ->danger()
                ->body('The invoice you are trying to edit does not exist or you do not have permission to access it.')
                ->send();
            
            $this->redirect(route('filament.dashboard.resources.client-invoices.index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Loading Invoice')
                ->danger()
                ->body('There was an error loading the invoice. Please try again.')
                ->send();
            
            \Log::error('Invoice builder mount error', [
                'user_id' => auth()->id(),
                'invoice_id' => $invoiceId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->redirect(route('filament.dashboard.resources.client-invoices.index'));
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Invoice Builder')
                    ->tabs([
                        Tabs\Tab::make('Invoice Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('invoice_client_id')
                                            ->label('Select Client')
                                            ->options(InvoiceClient::where('user_id', auth()->id())
                                                ->where('is_active', true)
                                                ->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->validationAttribute('Client Name'),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->validationAttribute('Client Email')
                                                    ->unique(InvoiceClient::class, 'email')
                                                    ->rule(function () {
                                                        return function (string $attribute, $value, \Closure $fail) {
                                                            if ($value && InvoiceClient::where('email', $value)
                                                                ->where('user_id', auth()->id())
                                                                ->exists()) {
                                                                $fail('This email is already registered for another client.');
                                                            }
                                                        };
                                                    }),
                                                Forms\Components\TextInput::make('company')
                                                    ->maxLength(255)
                                                    ->validationAttribute('Company Name'),
                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->validationAttribute('Phone Number'),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                try {
                                                    $data['user_id'] = auth()->id();
                                                    $client = InvoiceClient::create($data);
                                                    
                                                    Notification::make()
                                                        ->title('Client Created')
                                                        ->success()
                                                        ->body("Client '{$client->name}' has been added successfully.")
                                                        ->send();
                                                    
                                                    return $client->id;
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error Creating Client')
                                                        ->danger()
                                                        ->body('There was an error creating the client. Please try again.')
                                                        ->send();
                                                    
                                                    \Log::error('Client creation error', [
                                                        'user_id' => auth()->id(),
                                                        'data' => $data,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                    
                                                    throw $e;
                                                }
                                            }),

                                        Forms\Components\TextInput::make('invoice_number')
                                            ->label('Invoice Number')
                                            ->default(fn () => ClientInvoice::generateInvoiceNumber())
                                            ->required()
                                            ->maxLength(255)
                                            ->live()
                                            ->rule(function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    if ($value && ClientInvoice::where('invoice_number', $value)
                                                        ->where('user_id', auth()->id())
                                                        ->when($this->invoiceId, function ($query) {
                                                            return $query->where('id', '!=', $this->invoiceId);
                                                        })
                                                        ->exists()) {
                                                        $fail('This invoice number is already in use. Please choose a different number.');
                                                    }
                                                };
                                            }),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('invoice_date')
                                            ->label('Invoice Date')
                                            ->default(now())
                                            ->required()
                                            ->live(),

                                        Forms\Components\DatePicker::make('due_date')
                                            ->label('Due Date')
                                            ->default(now()->addDays(30))
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('currency')
                                            ->label('Currency')
                                            ->options(\App\Models\Currency::getSelectOptions())
                                            ->searchable()
                                            ->default('USD')
                                            ->native(false)
                                            ->required()
                                            ->live(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Line Items')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->label('Invoice Items')
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->label('Item / Service Description')
                                            ->required()
                                            ->placeholder('e.g., Web Development Services')
                                            ->columnSpanFull()
                                            ->live(),

                                        Forms\Components\Textarea::make('details')
                                            ->label('Additional Details (Optional)')
                                            ->rows(2)
                                            ->placeholder('Add specifications or notes...')
                                            ->columnSpanFull()
                                            ->live(),

                                        Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.01)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                        $set('amount', $state * ($get('unit_price') ?? 0));
                                                    }),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Unit Price ($)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required()
                                                    ->live(onBlur: true)
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
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Item')
                                    ->defaultItems(1)
                                    ->addActionLabel('+ Add Item')
                                    ->cloneable()
                                    ->columnSpanFull()
                                    ->live(),
                            ]),

                        Tabs\Tab::make('Pricing & Design')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make('Pricing')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('tax_rate')
                                                    ->label('Tax Rate (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->live(),

                                                Forms\Components\TextInput::make('discount_amount')
                                                    ->label('Discount Amount ($)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->live(),
                                            ]),
                                    ]),

                                Section::make('Invoice Design')
                                    ->schema([
                                        Forms\Components\Radio::make('template')
                                            ->label('Choose Template')
                                            ->options([
                                                'modern' => 'Modern Blue',
                                                'elegant' => 'Elegant Purple',
                                                'minimal' => 'Minimal Gray',
                                                'vibrant' => 'Vibrant Green',
                                            ])
                                            ->default('modern')
                                            ->inline()
                                            ->live()
                                            ->afterStateUpdated(function ($state) {
                                                $this->template = $state;
                                            }),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\ColorPicker::make('primary_color')
                                                    ->label('Primary Color')
                                                    ->default('#3b82f6')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state) {
                                                        $this->primaryColor = $state;
                                                    }),

                                                Forms\Components\ColorPicker::make('accent_color')
                                                    ->label('Accent Color')
                                                    ->default('#10b981')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state) {
                                                        $this->accentColor = $state;
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Notes & Terms')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Invoice Notes')
                                    ->rows(3)
                                    ->placeholder('Add special notes or instructions...')
                                    ->columnSpanFull()
                                    ->live(),

                                Forms\Components\Textarea::make('terms')
                                    ->label('Payment Terms & Conditions')
                                    ->rows(3)
                                    ->placeholder('e.g., Payment due within 30 days')
                                    ->columnSpanFull()
                                    ->live(),

                                Forms\Components\Textarea::make('footer')
                                    ->label('Invoice Footer')
                                    ->rows(2)
                                    ->placeholder('Thank you for your business!')
                                    ->columnSpanFull()
                                    ->live(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->contained(false),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save_draft')
                ->label('Save as Draft')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action('saveDraft'),

            // Preview PDF button commented out - using live preview instead
            // Forms\Components\Actions\Action::make('preview')
            //     ->label('Preview PDF')
            //     ->icon('heroicon-o-eye')
            //     ->color('info')
            //     ->action('previewPDF'),

            Forms\Components\Actions\Action::make('save_and_send')
                ->label('Save & Send to Client')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action('saveAndSend'),
        ];
    }

    public function saveDraft(): void
    {
        try {
            // Check invoice limit only for new invoices (not updates)
            if (!$this->invoiceId) {
                $featureService = app(\App\Services\SubscriptionFeatureService::class);
                $user = auth()->user();

                // Get count of invoices created this month
                $currentMonthCount = \App\Models\ClientInvoice::where('user_id', $user->id)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();

                $limit = $featureService->getFeatureValue($user, 'finance_invoices_limit', 50);

                if ($limit !== 'unlimited' && $limit !== 'true' && $currentMonthCount >= (int)$limit) {
                    Notification::make()
                        ->warning()
                        ->title('Invoice Limit Reached')
                        ->body("You've reached your monthly limit of {$limit} invoices. Upgrade to Pro for unlimited invoices!")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('upgrade')
                                ->label('View Plans')
                                ->url(route('filament.dashboard.pages.plans'))
                        ])
                        ->persistent()
                        ->send();
                    return;
                }
            }

            $data = $this->form->getState();

            // Validate required fields before processing
            $this->validateInvoiceData($data);

            if ($this->invoiceId) {
                // Update existing invoice
                $invoice = $this->updateInvoice($this->invoiceId, $data, 'draft');
                $message = "Invoice {$invoice->invoice_number} updated successfully.";
            } else {
                // Create new invoice
                $invoice = $this->createInvoice($data, 'draft');
                $message = "Invoice {$invoice->invoice_number} saved.";
            }

            Notification::make()
                ->title($this->invoiceId ? 'Invoice Updated' : 'Invoice Saved as Draft')
                ->success()
                ->body($message)
                ->send();

            $this->redirect(route('filament.dashboard.resources.client-invoices.index'));
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Filament handle them
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->danger()
                ->body('There was a database error while saving the invoice. Please try again.')
                ->send();
            
            \Log::error('Invoice save draft database error', [
                'user_id' => auth()->id(),
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Invoice')
                ->danger()
                ->body('There was an unexpected error while saving the invoice. Please try again.')
                ->send();
            
            \Log::error('Invoice save draft error', [
                'user_id' => auth()->id(),
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function previewPDF()
    {
        try {
            $data = $this->form->getState();

            // Validate required fields before preview
            $this->validateInvoiceData($data);

            // Create temporary invoice for preview
            $invoice = $this->createInvoice($data, 'draft', false);

            $pdfService = app(InvoicePDFService::class);

            // Generate PDF and return as stream (binary content from Browsershot)
            return $pdfService->stream($invoice);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Filament handle them
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Generating Preview')
                ->danger()
                ->body('There was an error generating the PDF preview. Please check your invoice data and try again.')
                ->send();

            \Log::error('Invoice PDF preview error', [
                'user_id' => auth()->id(),
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    public function saveAndSend(): void
    {
        try {
            // Check invoice limit only for new invoices (not updates)
            if (!$this->invoiceId) {
                $featureService = app(\App\Services\SubscriptionFeatureService::class);
                $user = auth()->user();

                // Get count of invoices created this month
                $currentMonthCount = \App\Models\ClientInvoice::where('user_id', $user->id)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();

                $limit = $featureService->getFeatureValue($user, 'finance_invoices_limit', 50);

                if ($limit !== 'unlimited' && $limit !== 'true' && $currentMonthCount >= (int)$limit) {
                    Notification::make()
                        ->warning()
                        ->title('Invoice Limit Reached')
                        ->body("You've reached your monthly limit of {$limit} invoices. Upgrade to Pro for unlimited invoices!")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('upgrade')
                                ->label('View Plans')
                                ->url(route('filament.dashboard.pages.plans'))
                        ])
                        ->persistent()
                        ->send();
                    return;
                }
            }

            $data = $this->form->getState();

            // Validate required fields before processing
            $this->validateInvoiceData($data);

            // Step 1: Save invoice with status "draft" (displayed as "saved" in UI)
            if ($this->invoiceId) {
                $invoice = $this->updateInvoice($this->invoiceId, $data, 'draft');
            } else {
                $invoice = $this->createInvoice($data, 'draft');
            }

            $pdfService = app(InvoicePDFService::class);
            $emailSent = $pdfService->emailToClient($invoice);

            if ($emailSent) {
                // Step 2: Update status to "sent" only after successful email
                $invoice->status = 'sent';
                $invoice->save();
                Notification::make()
                    ->title('Invoice Sent!')
                    ->success()
                    ->body("Invoice {$invoice->invoice_number} has been sent to the client.")
                    ->send();
            } else {
                Notification::make()
                    ->title('Invoice Saved but Email Failed')
                    ->warning()
                    ->body("Invoice {$invoice->invoice_number} was saved but could not be sent via email. You can manually send it later.")
                    ->send();
            }

            $this->redirect(route('filament.dashboard.resources.client-invoices.index'));
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Filament handle them
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->danger()
                ->body('There was a database error while saving the invoice. Please try again.')
                ->send();
            
            \Log::error('Invoice save and send database error', [
                'user_id' => auth()->id(),
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Processing Invoice')
                ->danger()
                ->body('There was an unexpected error while processing the invoice. Please try again.')
                ->send();
            
            \Log::error('Invoice save and send error', [
                'user_id' => auth()->id(),
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function updateInvoice(int $invoiceId, array $data, string $status): ClientInvoice
    {
        return DB::transaction(function () use ($invoiceId, $data, $status) {
            try {
                $invoice = ClientInvoice::findOrFail($invoiceId);

                // Update invoice data
                $invoice->update([
                    'invoice_client_id' => $data['invoice_client_id'],
                    'invoice_number' => $data['invoice_number'],
                    'status' => $status,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'currency' => $data['currency'],
                    'tax_rate' => $data['tax_rate'] ?? 0,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'footer' => $data['footer'] ?? null,
                    'template' => $this->template,
                    'primary_color' => $this->primaryColor,
                    'accent_color' => $this->accentColor,
                ]);

                // Delete existing items and recreate
                $invoice->items()->delete();

                // Create items
                $subtotal = 0;
                foreach ($data['items'] as $index => $itemData) {
                    $itemData['sort_order'] = $index + 1;
                    $itemData['client_invoice_id'] = $invoice->id;
                    $invoice->items()->create($itemData);
                    $subtotal += $itemData['amount'];
                }

                // Calculate totals
                $invoice->subtotal = $subtotal;
                $invoice->tax_amount = ($subtotal * $invoice->tax_rate) / 100;
                $invoice->total_amount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;
                $invoice->balance_due = $invoice->total_amount - $invoice->amount_paid;
                $invoice->save();

                return $invoice->load(['client', 'items', 'user']);
                
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw new \Exception('Invoice not found or you do not have permission to update it.');
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Invoice update database error', [
                    'invoice_id' => $invoiceId,
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A'
                ]);
                throw new \Exception('Database error occurred while updating the invoice.');
            }
        });
    }

    protected function createInvoice(array $data, string $status, bool $persist = true): ClientInvoice
    {
        return DB::transaction(function () use ($data, $status, $persist) {
            try {
                $invoiceData = [
                    'user_id' => auth()->id(),
                    'invoice_client_id' => $data['invoice_client_id'],
                    'invoice_number' => $data['invoice_number'],
                    'status' => $status,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'currency' => $data['currency'],
                    'tax_rate' => $data['tax_rate'] ?? 0,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'footer' => $data['footer'] ?? null,
                    'template' => $this->template,
                    'primary_color' => $this->primaryColor,
                    'accent_color' => $this->accentColor,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'balance_due' => 0,
                ];

                if ($persist) {
                    $invoice = ClientInvoice::create($invoiceData);
                } else {
                    $invoice = new ClientInvoice($invoiceData);
                    $invoice->id = 0;
                    $invoice->exists = true;
                }

                // Create items
                $subtotal = 0;
                $items = collect();

                foreach ($data['items'] as $index => $itemData) {
                    $itemData['sort_order'] = $index + 1;
                    $itemData['client_invoice_id'] = $invoice->id;

                    if ($persist) {
                        $invoice->items()->create($itemData);
                    } else {
                        $item = new ClientInvoiceItem($itemData);
                        $items->push($item);
                    }

                    $subtotal += $itemData['amount'];
                }

                // Set all items at once for non-persisted invoice
                if (!$persist) {
                    $invoice->setRelation('items', $items);
                }

                // Calculate totals
                $invoice->subtotal = $subtotal;
                $invoice->tax_amount = ($subtotal * $invoice->tax_rate) / 100;
                $invoice->total_amount = $subtotal + $invoice->tax_amount - $invoice->discount_amount;
                $invoice->balance_due = $invoice->total_amount;

                if ($persist) {
                    $invoice->save();
                    return $invoice->load(['client', 'items', 'user']);
                } else {
                    // Manually load relationships for non-persisted invoice
                    if ($invoice->invoice_client_id) {
                        $invoice->setRelation('client', \App\Models\InvoiceClient::find($invoice->invoice_client_id));
                    }
                    if ($invoice->user_id) {
                        $invoice->setRelation('user', \App\Models\User::find($invoice->user_id));
                    }
                    return $invoice;
                }
                
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Invoice creation database error', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A'
                ]);
                throw new \Exception('Database error occurred while creating the invoice.');
            }
        });
    }

    /**
     * Validate invoice data before processing
     */
    protected function validateInvoiceData(array $data): void
    {
        // Validate required fields
        if (empty($data['invoice_client_id'])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['invoice_client_id' => ['Please select a client for this invoice.']]
            );
        }

        if (empty($data['invoice_number'])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['invoice_number' => ['Invoice number is required.']]
            );
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['items' => ['At least one invoice item is required.']]
            );
        }

        // Validate items
        foreach ($data['items'] as $index => $item) {
            if (empty($item['description'])) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ["items.{$index}.description" => ['Item description is required.']]
                );
            }

            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ["items.{$index}.quantity" => ['Quantity must be greater than 0.']]
                );
            }

            if (empty($item['unit_price']) || $item['unit_price'] < 0) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ["items.{$index}.unit_price" => ['Unit price must be 0 or greater.']]
                );
            }
        }

        // Validate dates
        if (empty($data['invoice_date'])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['invoice_date' => ['Invoice date is required.']]
            );
        }

        if (empty($data['due_date'])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['due_date' => ['Due date is required.']]
            );
        }

        // Validate due date is after invoice date
        if ($data['due_date'] < $data['invoice_date']) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['due_date' => ['Due date must be after the invoice date.']]
            );
        }
    }
}
