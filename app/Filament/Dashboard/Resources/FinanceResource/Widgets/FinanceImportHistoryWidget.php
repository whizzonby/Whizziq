<?php

namespace App\Filament\Dashboard\Resources\FinanceResource\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class FinanceImportHistoryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Financial Data')
            ->query(
                // Combine expenses and revenue into a unified query using DB::table
                DB::table(
                    Expense::query()
                        ->where('user_id', auth()->id())
                        ->selectRaw("id, date, description, amount, category as type, 'expense' as source_type, created_at")
                        ->union(
                            RevenueSource::query()
                                ->where('user_id', auth()->id())
                                ->selectRaw("id, date, description, amount, source as type, 'revenue' as source_type, created_at")
                        )
                )
                ->orderByDesc('created_at')
                ->limit(50)
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('source_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'revenue' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->label('Type'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Category')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable()
                    ->color(fn ($record): string => $record->source_type === 'revenue' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Imported')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Type')
                    ->options([
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
