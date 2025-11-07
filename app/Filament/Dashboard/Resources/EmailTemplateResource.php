<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Email Communication';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Information')
                    ->schema([
TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Template Name')
                            ->helperText('Internal name for this template'),

Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(2)
                            ->label('Description')
                            ->helperText('What is this template used for?'),

Select::make('category')
                            ->options([
                                'follow_up' => 'Follow Up',
                                'welcome' => 'Welcome',
                                'appointment_reminder' => 'Appointment Reminder',
                                'marketing' => 'Marketing',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('other'),

Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active templates can be used'),

Toggle::make('is_default')
                            ->label('Set as Default')
                            ->helperText('Use this as the default template for this category'),
                    ])
                    ->columns(2),

Section::make('Email Content')
                    ->schema([
TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->label('Email Subject')
                            ->helperText('Use {{variable_name}} for dynamic content'),

RichEditor::make('body')
                            ->required()
                            ->label('Email Body')
                            ->helperText('Use {{variable_name}} for dynamic content')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),

Placeholder::make('available_variables')
                            ->label('Available Variables')
                            ->content(function () {
                                $variables = EmailTemplate::getAvailableVariables();
                                $list = collect($variables)
                                    ->map(fn($label, $key) => "{{" . $key . "}} - " . $label)
                                    ->join("\n");
                                return new \Illuminate\Support\HtmlString('<pre style="font-size: 12px; line-height: 1.6;">' . $list . '</pre>');
                            })
                            ->columnSpanFull(),
                    ]),

Section::make('Usage Statistics')
                    ->schema([
TextInput::make('times_used')
                            ->label('Times Used')
                            ->disabled()
                            ->default(0),

DateTimePicker::make('last_used_at')
                            ->label('Last Used')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->hidden(fn (?EmailTemplate $record) => $record === null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('category', $direction);
                    }),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('times_used')
                    ->label('Used')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'follow_up' => 'Follow Up',
                        'welcome' => 'Welcome',
                        'appointment_reminder' => 'Appointment Reminder',
                        'marketing' => 'Marketing',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All templates')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())->count();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreate(\App\Models\EmailTemplate::class, 'email_templates_limit') ?? false;
    }

}
