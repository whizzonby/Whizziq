<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MarketingMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 8;


    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                MarketingMetric::where('user_id', auth()->id())
                    ->where('date', $today)
                    ->orderBy('followers', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'facebook' => 'info',
                        'instagram' => 'danger',
                        'linkedin' => 'primary',
                        'twitter' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'facebook' => 'heroicon-o-globe-alt',
                        'instagram' => 'heroicon-o-camera',
                        'linkedin' => 'heroicon-o-briefcase',
                        'twitter' => 'heroicon-o-chat-bubble-left',
                        default => 'heroicon-o-globe-alt',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('followers')
                    ->label('Followers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('engagement')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reach')
                    ->label('Reach')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->heading('Marketing Presence')
            ->defaultSort('followers', 'desc')
            ->striped();
    }
}
