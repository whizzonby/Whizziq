<?php

namespace App\Filament\Dashboard\Resources\MarketingMetricResource\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class MarketingChannelBreakdownWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return $table
            ->query(
                MarketingMetric::query()
                    ->select([
                        'platform',
                        'channel',
                        DB::raw('SUM(ad_spend) as total_ad_spend'),
                        DB::raw('SUM(leads) as total_leads'),
                        DB::raw('SUM(conversions) as total_conversions'),
                        DB::raw('AVG(roi) as avg_roi'),
                        DB::raw('AVG(cost_per_conversion) as avg_cost_per_conversion'),
                        DB::raw('AVG(clv_cac_ratio) as avg_clv_cac_ratio'),
                        DB::raw('SUM(reach) as total_reach'),
                        DB::raw('SUM(engagement) as total_engagement'),
                        DB::raw('MAX(date) as latest_date'),
                    ])
                    ->where('user_id', auth()->id())
                    ->where('date', '>=', $startOfMonth)
                    ->groupBy('platform', 'channel')
                    ->orderByRaw('SUM(conversions) DESC')
                    ->orderBy('platform', 'asc')
                    ->orderBy('channel', 'asc')
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
                        'youtube' => 'danger',
                        'tiktok' => 'gray',
                        'google_ads' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Channel')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('total_ad_spend')
                    ->label('Ad Spend')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD')
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('total_leads')
                    ->label('Leads')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('total_conversions')
                    ->label('Conversions')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('avg_roi')
                    ->label('Avg ROI')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 200 => 'success',
                        $state >= 100 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                            ->label('Overall'),
                    ]),

                Tables\Columns\TextColumn::make('avg_cost_per_conversion')
                    ->label('Cost/Conv.')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->money('USD')
                            ->label('Avg'),
                    ]),

                Tables\Columns\TextColumn::make('avg_clv_cac_ratio')
                    ->label('CLV:CAC')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ':1' : '—')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 3 => 'success',
                        $state >= 2 => 'info',
                        $state >= 1 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_reach')
                    ->label('Reach')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('total_engagement')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('latest_date')
                    ->label('Last Updated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->heading('Marketing Channel Performance (This Month)')
            ->description('Aggregated performance metrics by platform and channel')
            ->striped();
    }
}
