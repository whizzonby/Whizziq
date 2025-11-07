<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\TaxDocumentResource\Pages;
use App\Models\TaxDocument;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\KeyValue;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class TaxDocumentResource extends Resource
{
    protected static ?string $model = TaxDocument::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Tax Documents';

    protected static UnitEnum|string|null $navigationGroup = 'Tax & Compliance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Upload')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Document')
                            ->directory('tax-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('file_type', $state->getClientOriginalExtension());
                                    $set('file_size', $state->getSize());
                                }
                            })
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull(),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'w2' => 'W-2 Wage Statement',
                                'w9' => 'W-9 Request for Taxpayer ID',
                                '1099_nec' => '1099-NEC Nonemployee Compensation',
                                '1099_misc' => '1099-MISC Miscellaneous Income',
                                '1099_int' => '1099-INT Interest Income',
                                '1099_div' => '1099-DIV Dividend Income',
                                'receipt' => 'Receipt',
                                'invoice' => 'Invoice',
                                'bank_statement' => 'Bank Statement',
                                'other' => 'Other Document',
                            ])
                            ->required()
                            ->searchable(),

                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., W-2 from XYZ Corp 2024'),

                        Select::make('tax_year')
                            ->label('Tax Year')
                            ->options(fn() => collect(range(now()->year - 5, now()->year + 1))->mapWithKeys(fn($y) => [$y => $y]))
                            ->default(now()->year)
                            ->required(),
                    ])->columns(2),

                Section::make('Document Details')
                    ->schema([
                        TextInput::make('payer_name')
                            ->label('Payer/Vendor Name')
                            ->maxLength(255)
                            ->placeholder('Company or individual name'),

                        TextInput::make('payer_tin')
                            ->label('Payer TIN/EIN')
                            ->mask('99-9999999')
                            ->placeholder('XX-XXXXXXX'),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),

                        Select::make('expense_id')
                            ->label('Link to Expense')
                            ->relationship('expense', 'description', fn($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->placeholder('Optional'),
                    ])->columns(2),

                Section::make('Verification')
                    ->schema([
                        Select::make('verification_status')
                            ->options([
                                'pending' => 'Pending Verification',
                                'verified' => 'Verified',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required(),

                        Textarea::make('verification_notes')
                            ->rows(3)
                            ->placeholder('Add notes about this document'),
                    ])->columns(1),

                Section::make('OCR Extracted Data')
                    ->schema([
                        Placeholder::make('ocr_status')
                            ->label('OCR Processing')
                            ->content(fn($record) => $record && $record->ocr_processed
                                ? '✅ Processed on ' . $record->ocr_processed_at?->format('M d, Y H:i')
                                : '⏳ Pending or not available'),

                        KeyValue::make('extracted_data')
                            ->label('Extracted Information')
                            ->disabled()
                            ->visible(fn($record) => $record && !empty($record->extracted_data)),
                    ])
                    ->visible(fn($record) => $record !== null)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_name')
                    ->label('Document')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn($record) => $record->getDocumentTypeName()),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'w2', 'w9' => 'success',
                        '1099_nec', '1099_misc', '1099_int', '1099_div' => 'info',
                        'receipt', 'invoice' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($record) => $record->getDocumentTypeName())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tax_year')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('ocr_processed')
                    ->label('OCR')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn($record) => $record->getFileSizeFormatted())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'w2' => 'W-2',
                        '1099_nec' => '1099-NEC',
                        '1099_misc' => '1099-MISC',
                        'receipt' => 'Receipt',
                        'invoice' => 'Invoice',
                    ]),

                Tables\Filters\SelectFilter::make('tax_year')
                    ->options(fn() => collect(range(now()->year - 5, now()->year + 1))->mapWithKeys(fn($y) => [$y => $y])),

                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\TernaryFilter::make('ocr_processed')
                    ->label('OCR Processed')
                    ->placeholder('All documents')
                    ->trueLabel('OCR Processed')
                    ->falseLabel('Not Processed'),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn($record) => Storage::download($record->file_path, $record->document_name))
                    ->color('primary'),

                Action::make('processOCR')
                    ->label('Process OCR')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->action(function ($record) {
                        $ocrService = app(\App\Services\DocumentOCRService::class);
                        $ocrService->processDocument($record);

                        \Filament\Notifications\Notification::make()
                            ->title('OCR Processing Started')
                            ->body('Document is being processed. Check back in a few moments.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->ocr_processed)
                    ->color('info'),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('verify')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(fn($r) => $r->update([
                                'verification_status' => 'verified',
                                'verified_at' => now(),
                            ]));
                        })
                        ->deselectRecordsAfterCompletion()
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxDocuments::route('/'),
            'create' => Pages\CreateTaxDocument::route('/create'),
            'edit' => Pages\EditTaxDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('verification_status', 'pending')
            ->count() ?: null;
    }

}
