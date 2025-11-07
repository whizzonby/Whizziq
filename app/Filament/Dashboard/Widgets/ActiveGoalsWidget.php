<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Dashboard\Resources\GoalResource;
use App\Models\Goal;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class ActiveGoalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;


    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Goal::query()
                    ->where('user_id', auth()->id())
                    ->active()
                    ->orderBy('target_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Goal')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->color(fn (string $state): string => match ($state) {
                        'annual' => 'info',
                        'quarterly' => 'primary',
                        'monthly' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn (Goal $record): string => $record->progress_percentage . '%')
                    ->badge()
                    ->color(fn (Goal $record): string => match (true) {
                        $record->progress_percentage >= 75 => 'success',
                        $record->progress_percentage >= 50 => 'primary',
                        $record->progress_percentage >= 25 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn (Goal $record): string => $record->status_icon)
                    ->color(fn (Goal $record): string => $record->status_color)
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Due Date')
                    ->date('M d')
                    ->description(fn (Goal $record): string => $record->days_remaining >= 0 ? $record->days_remaining . ' days' : 'Overdue'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Goal $record) => GoalResource::getUrl('view', ['record' => $record])),
            ])
            ->heading('Active Goals')
            ->description('Your current business objectives');
    }
}
