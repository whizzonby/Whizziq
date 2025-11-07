<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\AppointmentType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
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
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $type = AppointmentType::find($state);
                                    if ($type) {
                                        $set('title', $type->name);
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

                        Grid::make(2)->schema([
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
                        ]),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Office, Zoom, Phone, etc.'),

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

                Tables\Columns\TextColumn::make('location')
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Appointment $record): string => $record->status_color)
                    ->formatStateUsing(fn (Appointment $record): string => $record->status_label)
                    ->sortable(),
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
}
