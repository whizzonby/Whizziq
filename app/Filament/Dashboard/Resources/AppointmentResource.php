<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Venue;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Appointments';

    protected static UnitEnum|string|null $navigationGroup = 'Booking';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Appointment Details')
                    ->description('Basic appointment information')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Select::make('appointment_type_id')
                            ->label('Appointment Type')
                            ->relationship('appointmentType', 'name', fn ($query) =>
                                $query->where('user_id', auth()->id())->active()
                            )
                            ->required()
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $type = AppointmentType::find($state);
                                    if ($type) {
                                        $set('title', $type->name);
                                        // Set appointment format from type if not already set
                                        if (!$get('appointment_format')) {
                                            $set('appointment_format', $type->appointment_format ?? 'online');
                                        }
                                        // Set default venue if type has one
                                        if ($type->default_venue_id && !$get('venue_id')) {
                                            $set('venue_id', $type->default_venue_id);
                                        }
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->required()
                                    ->numeric()
                                    ->default(30),
                            ])
                            ->createOptionUsing(function ($data) {
                                return AppointmentType::create([
                                    'user_id' => auth()->id(),
                                    'name' => $data['name'],
                                    'duration_minutes' => $data['duration_minutes'],
                                ])->id;
                            }),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('start_datetime')
                            ->label('Start Date & Time')
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $typeId = $get('appointment_type_id');
                                if ($typeId && $state) {
                                    $type = AppointmentType::find($typeId);
                                    $start = \Carbon\Carbon::parse($state);
                                    $end = $start->copy()->addMinutes($type->duration_minutes);
                                    $set('end_datetime', $end);
                                }
                            }),

                        Forms\Components\DateTimePicker::make('end_datetime')
                            ->label('End Date & Time')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('appointment_format')
                            ->label('Format')
                            ->options([
                                'online' => 'Online (Video/Meeting)',
                                'in_person' => 'In-Person',
                                'hybrid' => 'Hybrid (Both Online & In-Person)',
                                'phone' => 'Phone Call',
                            ])
                            ->default('online')
                            ->live()
                            ->reactive()
                            ->helperText('Overrides appointment type format if needed'),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Office, Zoom, Phone, etc.')
                            ->helperText('Free-text location (legacy field, use Venue below for structured locations)')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show',
                            ])
                            ->default('scheduled')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Attendee Information')
                    ->description('Client/attendee details')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('attendee_name')
                            ->label('Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('attendee_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('attendee_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('attendee_company')
                            ->label('Company')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Recurring Pattern')
                    ->description('Make this appointment repeat on a schedule')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Make this a recurring appointment')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('recurrence_type', null);
                                    $set('recurrence_interval', 1);
                                    $set('recurrence_days', null);
                                    $set('recurrence_end_date', null);
                                    $set('recurrence_count', null);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('recurrence_type')
                            ->label('Repeat')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->visible(fn ($get) => $get('is_recurring')),

                        Forms\Components\TextInput::make('recurrence_interval')
                            ->label('Repeat Every')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(12)
                            ->suffix(fn ($get) => match($get('recurrence_type')) {
                                'daily' => 'day(s)',
                                'weekly' => 'week(s)',
                                'monthly' => 'month(s)',
                                default => ''
                            })
                            ->visible(fn ($get) => $get('is_recurring')),

                        Forms\Components\CheckboxList::make('recurrence_days')
                            ->label('Repeat On')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->columns(4)
                            ->visible(fn ($get) => $get('is_recurring') && $get('recurrence_type') === 'weekly')
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('recurrence_end_date')
                            ->label('End Date')
                            ->helperText('Leave blank for unlimited')
                            ->native(false)
                            ->visible(fn ($get) => $get('is_recurring')),

                        Forms\Components\TextInput::make('recurrence_count')
                            ->label('OR Number of Occurrences')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Leave blank if using end date')
                            ->visible(fn ($get) => $get('is_recurring')),

                        Forms\Components\Placeholder::make('recurrence_info')
                            ->label('')
                            ->content(fn ($get) => $get('is_recurring')
                                ? '💡 After saving, recurring instances will be automatically created'
                                : ''
                            )
                            ->visible(fn ($get) => $get('is_recurring'))
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Section::make('Location & Venue')
                    ->description('Physical location details for in-person appointments')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Venue')
                            ->relationship('venue', 'name', fn ($query) =>
                                $query->where('user_id', auth()->id())->active()
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Select a venue for in-person appointments')
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['in_person', 'hybrid'])),

                        Forms\Components\TextInput::make('room_name')
                            ->label('Room/Area Name')
                            ->maxLength(255)
                            ->placeholder('Conference Room A, Main Office, etc.')
                            ->helperText('Specific room or area within the venue')
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['in_person', 'hybrid']) && $get('venue_id')),
                    ])
                    ->collapsed()
                    ->visible(fn ($get) => in_array($get('appointment_format'), ['in_person', 'hybrid'])),

                Section::make('Online Meeting Details')
                    ->description('Meeting platform information for online appointments')
                    ->icon('heroicon-o-video-camera')
                    ->schema([
                        Forms\Components\Select::make('meeting_platform')
                            ->label('Meeting Platform')
                            ->options([
                                'zoom' => 'Zoom',
                                'google_meet' => 'Google Meet',
                            ])
                            ->native(false)
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['online', 'hybrid'])),

                        Forms\Components\TextInput::make('meeting_url')
                            ->label('Meeting URL')
                            ->url()
                            ->maxLength(500)
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['online', 'hybrid'])),

                        Forms\Components\TextInput::make('meeting_id')
                            ->label('Meeting ID')
                            ->maxLength(255)
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['online', 'hybrid'])),

                        Forms\Components\TextInput::make('meeting_password')
                            ->label('Meeting Password')
                            ->password()
                            ->maxLength(255)
                            ->visible(fn ($get) => in_array($get('appointment_format'), ['online', 'hybrid'])),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->visible(fn ($get) => in_array($get('appointment_format'), ['online', 'hybrid'])),

                Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (Appointment $record): string => $record->status_icon)
                    ->color(fn (Appointment $record): string => $record->status_color)
                    ->tooltip(fn (Appointment $record): string => $record->status_label),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Appointment $record): ?string =>
                        $record->attendee_name ? 'with ' . $record->attendee_name : null
                    ),

                Tables\Columns\TextColumn::make('appointmentType.name')
                    ->label('Type')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Date & Time')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->description(fn (Appointment $record): string =>
                        $record->duration_minutes . ' min'
                    ),

                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Venue')
                    ->icon('heroicon-o-map-pin')
                    ->badge()
                    ->color('success')
                    ->toggleable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location (Legacy)')
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('—'),

                Tables\Columns\TextColumn::make('appointment_format')
                    ->label('Format')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'online' => 'Online',
                        'in_person' => 'In-Person',
                        'hybrid' => 'Hybrid',
                        'phone' => 'Phone',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'online' => 'primary',
                        'in_person' => 'success',
                        'hybrid' => 'warning',
                        'phone' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Appointment $record): string => $record->status_color)
                    ->formatStateUsing(fn (Appointment $record): string => $record->status_label)
                    ->sortable(),

                Tables\Columns\IconColumn::make('calendar_synced')
                    ->label('Calendar')
                    ->icon(fn (Appointment $record): string =>
                        $record->calendar_event_id ? 'heroicon-s-check-circle' : 'heroicon-o-x-circle'
                    )
                    ->color(fn (Appointment $record): string =>
                        $record->calendar_event_id ? 'success' : 'gray'
                    )
                    ->tooltip(fn (Appointment $record): string =>
                        $record->calendar_event_id
                            ? 'Synced to Google Calendar' . ($record->calendar_synced_at ? ' at ' . $record->calendar_synced_at->format('M d, g:i A') : '')
                            : 'Not synced to calendar'
                    )
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->icon(fn (Appointment $record): string =>
                        $record->is_recurring ? 'heroicon-s-arrow-path' : 'heroicon-o-minus'
                    )
                    ->color(fn (Appointment $record): string =>
                        $record->is_recurring ? 'info' : 'gray'
                    )
                    ->tooltip(fn (Appointment $record): string =>
                        $record->is_recurring
                            ? ($record->isRecurringParent() ? 'Recurring series: ' . $record->recurrence_description : 'Part of recurring series')
                            : 'One-time appointment'
                    )
                    ->toggleable(),
            ])
            ->defaultSort('start_datetime', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('appointment_type_id')
                    ->label('Type')
                    ->relationship('appointmentType', 'name')
                    ->preload(),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Only')
                    ->query(fn (Builder $query) => $query->upcoming()),

                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->today()),

                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query) => $query->thisWeek()),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Appointment $record) => $record->confirm())
                    ->visible(fn (Appointment $record) => $record->status === 'scheduled')
                    ->requiresConfirmation(false),

                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->action(fn (Appointment $record) => $record->markAsCompleted())
                    ->visible(fn (Appointment $record) => in_array($record->status, ['scheduled', 'confirmed']))
                    ->requiresConfirmation(false),

                Action::make('sync_calendar')
                    ->label('Sync to Calendar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (Appointment $record) {
                        $calendarService = app(\App\Services\CalendarSyncService::class);
                        $result = $calendarService->pushAppointmentToCalendar($record, forceCreate: true);

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Calendar Sync Successful')
                                ->success()
                                ->body('Appointment synced to Google Calendar')
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Calendar Sync Failed')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
                    })
                    ->visible(fn (Appointment $record) => in_array($record->status, ['scheduled', 'confirmed']))
                    ->requiresConfirmation(false),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No appointments yet')
            ->emptyStateDescription('Create your first appointment to get started')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->upcoming()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreate(Appointment::class, 'appointments_limit') ?? false;
    }

}
