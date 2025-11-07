<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAppointmentsWidget extends BaseWidget
{
    protected static ?string $heading = '📅 Recent Appointments';
    
    protected static ?int $sort = 27;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::where('user_id', auth()->id())
                    ->orderBy('start_datetime', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Appointment $record): string => $record->status_color)
                    ->formatStateUsing(fn (Appointment $record): string => $record->status_label),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->icon(fn (Appointment $record): string =>
                        $record->is_recurring || $record->recurring_parent_id ? 'heroicon-s-arrow-path' : 'heroicon-o-minus'
                    )
                    ->color(fn (Appointment $record): string =>
                        $record->is_recurring || $record->recurring_parent_id ? 'info' : 'gray'
                    ),
            ])
            ->heading('📅 Recent Appointments')
            ->description('Your latest appointments and bookings');
    }
}
