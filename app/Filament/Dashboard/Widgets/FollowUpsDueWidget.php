<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\FollowUpReminder;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class FollowUpsDueWidget extends BaseWidget
{
    protected static ?string $heading = '📞 Follow-ups Due';
    
    protected static ?int $sort = 50;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FollowUpReminder::query()
                    ->with(['contact', 'deal'])
                    ->where('user_id', Auth::id())
                    ->where('status', 'pending')
                    ->where('remind_at', '<=', now()->addDays(7))
                    ->orderBy('remind_at', 'asc')
            )
            ->heading('Follow-Ups Due This Week')
            ->columns([
                Tables\Columns\TextColumn::make('remind_at')
                    ->label('Due')
                    ->dateTime()
                    ->sortable()
                    ->color(fn (FollowUpReminder $record) => $record->is_overdue ? 'danger' : ($record->is_due ? 'warning' : 'success'))
                    ->badge(),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->searchable()
                    ->url(fn (FollowUpReminder $record) => route('filament.dashboard.resources.contacts.edit', $record->contact)),

                Tables\Columns\TextColumn::make('contact.company')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Reminder')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'high',
                        'warning' => 'medium',
                        'secondary' => 'low',
                    ]),

                Tables\Columns\TextColumn::make('deal.title')
                    ->label('Related Deal')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('days_until_due')
                    ->label('Days')
                    ->formatStateUsing(fn (FollowUpReminder $record) =>
                        $record->is_overdue
                            ? $record->days_until_due * -1 . ' overdue'
                            : ($record->is_due ? 'Today' : 'in ' . $record->days_until_due)
                    )
                    ->color(fn (FollowUpReminder $record) => $record->is_overdue ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (FollowUpReminder $record) {
                        $record->markAsCompleted();
                    }),

                Action::make('snooze')
                    ->label('Snooze')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('days')
                            ->label('Snooze for')
                            ->options([
                                1 => '1 day',
                                3 => '3 days',
                                7 => '1 week',
                                14 => '2 weeks',
                            ])
                            ->required(),
                    ])
                    ->action(function (FollowUpReminder $record, array $data) {
                        $record->remind_at = now()->addDays($data['days'] ?? 1);
                        $record->save();
                    }),
            ])
            ->paginated([10]);
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Only show if there are follow-ups due
        return FollowUpReminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('remind_at', '<=', now()->addDays(7))
            ->exists();
    }
}
