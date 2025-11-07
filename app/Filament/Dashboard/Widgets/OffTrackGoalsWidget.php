<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Dashboard\Resources\GoalResource;
use App\Models\Goal;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class OffTrackGoalsWidget extends BaseWidget
{
    protected static ?string $heading = '⚠️ Off-Track Goals';
    
    protected static ?int $sort = 22;


    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Goal::query()
                    ->where('user_id', auth()->id())
                    ->offTrack()
                    ->orderBy('target_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Goal')
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn (Goal $record): string => $record->status_icon)
                    ->color(fn (Goal $record): string => $record->status_color)
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn (Goal $record): string => $record->progress_percentage . '%')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->description(fn (Goal $record): string => $record->days_remaining . ' days remaining'),

                Tables\Columns\IconColumn::make('ai_suggestions')
                    ->label('AI Help')
                    ->boolean()
                    ->trueIcon('heroicon-s-sparkles')
                    ->falseIcon('heroicon-o-sparkles')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Goal $record) => $record->ai_suggestions ? 'AI suggestions available' : 'No suggestions yet')
                    ->getStateUsing(fn (Goal $record) => !empty($record->ai_suggestions)),
            ])
            ->actions([
                Action::make('check_in')
                    ->label('Check-in')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->url(fn (Goal $record) => GoalResource::getUrl('check-in', ['record' => $record])),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Goal $record) => GoalResource::getUrl('view', ['record' => $record])),
            ])
            ->heading('⚠️ Goals Needing Attention')
            ->description('These goals are at risk or off track - take action now!')
            ->emptyStateHeading('All goals are on track!')
            ->emptyStateDescription('Great job! Keep up the momentum.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
