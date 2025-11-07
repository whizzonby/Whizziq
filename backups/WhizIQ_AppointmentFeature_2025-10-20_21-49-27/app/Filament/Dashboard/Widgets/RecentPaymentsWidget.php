<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\ClientPayment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClientPayment::query()
                    ->where('user_id', Auth::id())
                    ->with(['invoice', 'client'])
                    ->orderBy('payment_date', 'desc')
                    ->limit(10)
            )
            ->heading('Recent Payments')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn (ClientPayment $record) => route('filament.dashboard.resources.client-invoices.view', $record->invoice)),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Method')
                    ->badge(),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->placeholder('-')
                    ->limit(20),
            ])
            ->paginated([10]);
    }
}
