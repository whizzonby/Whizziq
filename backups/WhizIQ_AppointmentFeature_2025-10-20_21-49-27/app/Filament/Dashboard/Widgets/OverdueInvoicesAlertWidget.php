<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\ClientInvoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OverdueInvoicesAlertWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClientInvoice::query()
                    ->where('user_id', Auth::id())
                    ->where('status', 'overdue')
                    ->orderBy('due_date', 'asc')
            )
            ->heading('Overdue Invoices - Action Required')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (ClientInvoice $record) => $record->days_overdue . ' days'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Amount Due')
                    ->money('USD')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('last_reminder_sent_at')
                    ->label('Last Reminder')
                    ->dateTime()
                    ->placeholder('Never')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (ClientInvoice $record) {
                        // TODO: Implement email sending
                        $record->recordReminderSent();
                    }),

                Tables\Actions\Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->url(fn (ClientInvoice $record) => route('filament.dashboard.resources.client-invoices.edit', $record)),
            ])
            ->paginated([5, 10]);
    }

    public static function canView(): bool
    {
        // Only show if there are overdue invoices
        return ClientInvoice::where('user_id', Auth::id())
            ->where('status', 'overdue')
            ->exists();
    }
}
