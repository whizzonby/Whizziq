<?php

namespace App\Filament\Admin\Resources\Pages;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pages');
    }

    public static function getModelLabel(): string
    {
        return __('Page');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('title')
                        ->required()
                        ->label(__('Title'))
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (! $get('slug')) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    RichEditor::make('content')
                        ->required()
                        ->label(__('Content'))
                        ->floatingToolbars([
                            'paragraph' => [
                                'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                            ],
                            'heading' => [
                                'h1', 'h2', 'h3',
                            ],
                            'table' => [
                                'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                                'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                                'tableMergeCells', 'tableSplitCell',
                                'tableToggleHeaderRow',
                                'tableDelete',
                            ],
                        ])
                        ->fileAttachmentsDirectory('page-images')
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label(__('Meta Description'))
                        ->maxLength(160)
                        ->helperText(__('SEO description (max 160 characters). This will be used in search engine results.'))
                        ->rows(2)
                        ->columnSpanFull(),
                    TextInput::make('meta_keywords')
                        ->label(__('Meta Keywords'))
                        ->maxLength(255)
                        ->helperText(__('Comma-separated keywords for SEO.'))
                        ->columnSpanFull(),
                ])->columnSpan(2),
                Section::make([
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('Will be used in the URL. Example: privacy-policy'))
                        ->dehydrateStateUsing(fn ($state) => Str::slug($state))
                        ->maxLength(255),
                    Select::make('page_type')
                        ->label(__('Page Type'))
                        ->required()
                        ->options([
                            'policy' => __('Policy'),
                            'legal' => __('Legal'),
                            'general' => __('General'),
                            'other' => __('Other'),
                        ])
                        ->default('general'),
                    Select::make('author_id')
                        ->label(__('Author'))
                        ->default(auth()->id())
                        ->required()
                        ->options(
                            User::admin()->get()->sortBy('name')
                                ->mapWithKeys(function ($user) {
                                    return [$user->id => $user->getPublicName()];
                                })
                                ->toArray()
                        ),
                    TextInput::make('sort_order')
                        ->label(__('Sort Order'))
                        ->numeric()
                        ->default(0)
                        ->helperText(__('Used for ordering in navigation. Lower numbers appear first.')),
                    Toggle::make('is_published')
                        ->label(__('Is Published'))
                        ->default(true)
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label(__('Published At'))
                        ->default(now())
                        ->required(function ($state, Get $get) {
                            return $get('is_published');
                        }),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('Slug copied!'))
                    ->copyMessageDuration(1500),
                TextColumn::make('page_type')
                    ->label(__('Type'))
                    ->badge()
                    ->colors([
                        'danger' => 'policy',
                        'warning' => 'legal',
                        'success' => 'general',
                        'gray' => 'other',
                    ])
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label(__('Author'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label(__('Published'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('Published Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['author']))
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Page $record): string => $record->url)
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Pages');
    }
}
