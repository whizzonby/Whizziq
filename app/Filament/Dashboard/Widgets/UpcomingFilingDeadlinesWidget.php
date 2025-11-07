<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\TaxPeriod;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class UpcomingFilingDeadlinesWidget extends BaseWidget
{
    protected static ?string $heading = '📅 Upcoming Tax Deadlines';

    protected static ?int $sort = 12;


    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TaxPeriod::query()
                    ->where('user_id', auth()->id())
                    ->where('status', '!=', 'filed')
                    ->whereNotNull('filing_deadline')
                    ->orderBy('filing_deadline', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tax Period')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->color(fn (string $state): string => match($state) {
                        'quarterly' => 'info',
                        'annual' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('period_range')
                    ->label('Period')
                    ->formatStateUsing(fn (TaxPeriod $record): string =>
                        $record->start_date->format('M d, Y') . ' - ' . $record->end_date->format('M d, Y')
                    ),

                Tables\Columns\TextColumn::make('filing_deadline')
                    ->label('Filing Deadline')
                    ->date('M d, Y')
                    ->description(fn (TaxPeriod $record): ?string =>
                        $this->getDeadlineDescription($record)
                    )
                    ->color(fn (TaxPeriod $record): string =>
                        $this->getDeadlineColor($record)
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'closed' => 'warning',
                        'filed' => 'info',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('close')
                    ->label('Close Period')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (TaxPeriod $record) => $record->isActive())
                    ->action(fn (TaxPeriod $record) => $record->close())
                    ->requiresConfirmation(),

                Action::make('file')
                    ->label('Mark as Filed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TaxPeriod $record) => !$record->isFiled())
                    ->action(fn (TaxPeriod $record) => $record->markAsFiled())
                    ->requiresConfirmation(),
            ])
            ->heading('📋 Upcoming Tax Filing Deadlines')
            ->description('Tax periods requiring filing')
            ->emptyStateHeading('No upcoming deadlines')
            ->emptyStateDescription('All tax periods are up to date!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function getDeadlineDescription(TaxPeriod $record): ?string
    {
        if (!$record->filing_deadline) {
            return null;
        }

        $now = Carbon::now();
        $deadline = Carbon::parse($record->filing_deadline);

        if ($deadline->isPast()) {
            return '⚠️ Overdue';
        }

        if ($deadline->isToday()) {
            return '🔥 Due Today';
        }

        $daysUntil = $now->diffInDays($deadline);

        if ($daysUntil <= 7) {
            return '⚠️ ' . $daysUntil . ' days left';
        }

        return $daysUntil . ' days left';
    }

    protected function getDeadlineColor(TaxPeriod $record): string
    {
        if (!$record->filing_deadline) {
            return 'gray';
        }

        $now = Carbon::now();
        $deadline = Carbon::parse($record->filing_deadline);

        if ($deadline->isPast()) {
            return 'danger';
        }

        if ($deadline->isToday()) {
            return 'warning';
        }

        $daysUntil = $now->diffInDays($deadline);

        if ($daysUntil <= 7) {
            return 'warning';
        }

        return 'gray';
    }
}
