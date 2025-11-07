<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ContactResource\Pages;
use App\Models\Contact;
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
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Contacts';

    protected static UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('company')
                                    ->label('Company')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('job_title')
                                    ->label('Job Title')
                                    ->maxLength(255),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'client' => 'Client',
                                        'lead' => 'Lead',
                                        'partner' => 'Partner',
                                        'investor' => 'Investor',
                                        'vendor' => 'Vendor',
                                        'other' => 'Other',
                                    ])
                                    ->default('lead')
                                    ->native(false)
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'archived' => 'Archived',
                                    ])
                                    ->default('active')
                                    ->native(false)
                                    ->required(),

                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'low' => 'Low',
                                        'medium' => 'Medium',
                                        'high' => 'High',
                                        'vip' => 'VIP',
                                    ])
                                    ->default('medium')
                                    ->native(false)
                                    ->required(),
                            ]),
                    ]),

                Section::make('Contact Details')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('website')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('linkedin_url')
                                    ->label('LinkedIn URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://linkedin.com/in/'),

                                Forms\Components\TextInput::make('twitter_handle')
                                    ->label('Twitter Handle')
                                    ->maxLength(255)
                                    ->prefix('@'),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('Address')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Street Address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('state')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP/Postal')
                                    ->maxLength(255),

                                Forms\Components\Select::make('country')
                                    ->options([
                                        'USA' => 'USA',
                                        'CAN' => 'Canada',
                                        'GBR' => 'UK',
                                        'AUS' => 'Australia',
                                    ])
                                    ->default('USA')
                                    ->native(false)
                                    ->searchable(),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('Relationship Management')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('last_contact_date')
                                    ->label('Last Contact Date'),

                                Forms\Components\DatePicker::make('next_follow_up_date')
                                    ->label('Next Follow-Up Date'),

                                Forms\Components\Select::make('relationship_strength')
                                    ->label('Relationship')
                                    ->options([
                                        'cold' => 'Cold',
                                        'warm' => 'Warm',
                                        'hot' => 'Hot',
                                    ])
                                    ->default('warm')
                                    ->native(false),
                            ]),

                        Forms\Components\TextInput::make('source')
                            ->label('Lead Source')
                            ->maxLength(255)
                            ->placeholder('e.g., referral, website, event'),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags'),
                    ])
                    ->collapsed(),

                Section::make('Business Value')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('lifetime_value')
                            ->label('Lifetime Value')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->disabled()
                            ->helperText('Auto-calculated from won deals'),
                    ])
                    ->collapsed(),

                Section::make('Notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('job_title')
                    ->label('Title')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'client',
                        'primary' => 'lead',
                        'warning' => 'partner',
                        'info' => 'investor',
                        'secondary' => 'vendor',
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'vip',
                        'warning' => 'high',
                        'primary' => 'medium',
                        'secondary' => 'low',
                    ]),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_contact_date')
                    ->label('Last Contact')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->diffInDays(now()) > 30 ? 'danger' : null),

                Tables\Columns\TextColumn::make('next_follow_up_date')
                    ->label('Next Follow-Up')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('relationship_strength')
                    ->label('Relationship')
                    ->colors([
                        'danger' => 'cold',
                        'warning' => 'warm',
                        'success' => 'hot',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('lifetime_value')
                    ->label('LTV')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deals_count')
                    ->label('Deals')
                    ->counts('deals')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('last_contact_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'client' => 'Client',
                        'lead' => 'Lead',
                        'partner' => 'Partner',
                        'investor' => 'Investor',
                        'vendor' => 'Vendor',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'vip' => 'VIP',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                Tables\Filters\Filter::make('needs_follow_up')
                    ->label('Needs Follow-Up')
                    ->query(fn (Builder $query) => $query->needsFollowUp()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Follow-Up')
                    ->query(fn (Builder $query) => $query->where('next_follow_up_date', '<', now())->where('status', 'active')),
            ])
            ->actions([
                EditAction::make(),

                Action::make('log_interaction')
                    ->label('Log Interaction')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'call' => 'Call',
                                'email' => 'Email',
                                'meeting' => 'Meeting',
                                'note' => 'Note',
                                'demo' => 'Demo',
                            ])
                            ->default('note')
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('subject')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3),

                        Forms\Components\DateTimePicker::make('interaction_date')
                            ->label('Date & Time')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->suffix('min'),

                        Forms\Components\Select::make('outcome')
                            ->options([
                                'positive' => 'Positive',
                                'neutral' => 'Neutral',
                                'negative' => 'Negative',
                                'follow_up_needed' => 'Follow-Up Needed',
                            ])
                            ->native(false),
                    ])
                    ->action(function (Contact $record, array $data) {
                        $record->logInteraction(
                            type: $data['type'],
                            description: $data['description'],
                            interactionDate: $data['interaction_date'],
                            subject: $data['subject'] ?? null,
                            durationMinutes: $data['duration_minutes'] ?? null,
                            outcome: $data['outcome'] ?? null
                        );

                        Notification::make()
                            ->title('Interaction Logged')
                            ->success()
                            ->send();
                    }),

                Action::make('schedule_follow_up')
                    ->label('Schedule Follow-Up')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Send proposal'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3),

                        Forms\Components\DateTimePicker::make('remind_at')
                            ->label('Remind Me On')
                            ->default(now()->addDays(7))
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                            ])
                            ->default('medium')
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (Contact $record, array $data) {
                        $record->scheduleFollowUp(
                            date: $data['remind_at'],
                            title: $data['title'],
                            description: $data['description'] ?? null,
                            priority: $data['priority']
                        );

                        Notification::make()
                            ->title('Follow-Up Scheduled')
                            ->success()
                            ->body("Reminder set for {$data['remind_at']->format('M d, Y')}")
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->needsFollowUp()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
