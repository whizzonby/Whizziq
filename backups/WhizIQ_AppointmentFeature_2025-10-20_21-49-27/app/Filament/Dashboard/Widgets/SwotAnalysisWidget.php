<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\SwotAnalysis;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SwotAnalysisWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SwotAnalysis::where('user_id', auth()->id())
                    ->orderBy('type')
                    ->orderBy('priority', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'strength' => 'success',
                        'weakness' => 'danger',
                        'opportunity' => 'info',
                        'threat' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->toggleable(),
            ])
            ->heading('SWOT Analysis')
            ->defaultSort('priority', 'desc')
            ->striped();
    }
}
