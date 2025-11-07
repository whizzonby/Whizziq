<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\DocumentVaultResource\Pages;
use App\Models\DocumentVault;
use App\Services\DocumentAnalysisService;
use App\Services\TaskExtractionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class DocumentVaultResource extends Resource
{
    protected static ?string $model = DocumentVault::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Document Vault';

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Document Vault';

    protected static UnitEnum|string|null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->description('Upload and organize your documents')
                    ->icon('heroicon-o-document-plus')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->disk('public')
                            ->directory('documents')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                            ])
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->helperText('Max size: 10MB. Supported: PDF, Word, Excel, PowerPoint, Text, Images')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Employment Contract 2024')
                            ->columnSpan(2),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'legal' => 'Legal Documents',
                                'financial' => 'Financial Documents',
                                'business' => 'Business Documents',
                                'personal' => 'Personal Documents',
                                'education' => 'Education',
                                'medical' => 'Medical Records',
                                'contract' => 'Contracts',
                                'invoice' => 'Invoices',
                                'report' => 'Reports',
                                'proposal' => 'Proposals',
                                'other' => 'Other',
                            ])
                            ->native(false)
                            ->searchable()
                            ->placeholder('Select category')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of this document...')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags (press Enter)')
                            ->helperText('Add tags to help organize and find this document later')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Mark as Favorite')
                            ->inline(false)
                            ->helperText('Favorite documents appear at the top of your vault'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->action(function (DocumentVault $record) {
                        $record->update(['is_favorite' => !$record->is_favorite]);

                        Notification::make()
                            ->title($record->is_favorite ? 'Added to Favorites' : 'Removed from Favorites')
                            ->success()
                            ->send();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Document Title')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (DocumentVault $record): ?string => $record->description ? Str::limit($record->description, 50) : null)
                    ->wrap(),

                Tables\Columns\IconColumn::make('file_icon')
                    ->label('Type')
                    ->icon(fn (DocumentVault $record) => $record->file_icon)
                    ->color(fn (DocumentVault $record) => $record->file_color)
                    ->tooltip(fn (DocumentVault $record) => strtoupper($record->file_type)),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (DocumentVault $record) => $record->category_color)
                    ->icon(fn (DocumentVault $record) => $record->category_icon)
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('_', ' ', Str::title($state)) : 'Uncategorized')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('file_size_human')
                    ->label('Size')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('file_size', $direction)),

                Tables\Columns\IconColumn::make('analyzed_at')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-s-sparkles')
                    ->falseIcon('heroicon-o-sparkles')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (DocumentVault $record) => $record->isAnalyzed() ? 'AI analyzed' : 'Not analyzed')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'legal' => 'Legal Documents',
                        'financial' => 'Financial Documents',
                        'business' => 'Business Documents',
                        'personal' => 'Personal Documents',
                        'education' => 'Education',
                        'medical' => 'Medical Records',
                        'contract' => 'Contracts',
                        'invoice' => 'Invoices',
                        'report' => 'Reports',
                        'proposal' => 'Proposals',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('file_type')
                    ->label('File Type')
                    ->options([
                        'pdf' => 'PDF',
                        'doc' => 'Word (DOC)',
                        'docx' => 'Word (DOCX)',
                        'xls' => 'Excel (XLS)',
                        'xlsx' => 'Excel (XLSX)',
                        'ppt' => 'PowerPoint (PPT)',
                        'pptx' => 'PowerPoint (PPTX)',
                        'txt' => 'Text',
                        'jpg' => 'JPEG Image',
                        'png' => 'PNG Image',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favorites')
                    ->placeholder('All documents')
                    ->trueLabel('Favorites only')
                    ->falseLabel('Not favorites'),

                Tables\Filters\TernaryFilter::make('analyzed_at')
                    ->label('AI Analysis')
                    ->placeholder('All documents')
                    ->trueLabel('Analyzed only')
                    ->falseLabel('Not analyzed')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('analyzed_at'),
                        false: fn (Builder $query) => $query->whereNull('analyzed_at'),
                    ),
            ])
            ->actions([
                Action::make('extract_tasks')
                    ->label('Extract Tasks')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (DocumentVault $record) {
                        $service = app(TaskExtractionService::class);
                        $tasks = $service->createTasksFromDocument($record, auth()->user());

                        if (empty($tasks)) {
                            Notification::make()
                                ->title('No Tasks Found')
                                ->body('AI couldn\'t identify action items in this document.')
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tasks Extracted!')
                                ->body(count($tasks) . ' action item(s) have been added to your task list.')
                                ->success()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Extract Action Items')
                    ->modalDescription('AI will scan this document and automatically create tasks from any action items found.')
                    ->modalIcon('heroicon-o-sparkles'),

                Action::make('view')
                    ->label('View & Analyze')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (DocumentVault $record) => DocumentVaultResource::getUrl('view', ['record' => $record])),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (DocumentVault $record) {
                        return $record->download();
                    }),

                EditAction::make()
                    ->color('gray'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No documents uploaded yet')
            ->emptyStateDescription('Start building your document vault by uploading your first file')
            ->emptyStateIcon('heroicon-o-folder-open')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Upload Your First Document')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentVaults::route('/'),
            'create' => Pages\CreateDocumentVault::route('/create'),
            'view' => Pages\ViewDocumentVault::route('/{record}'),
            'edit' => Pages\EditDocumentVault::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())->count();
        return $count > 0 ? (string) $count : null;
    }

}
