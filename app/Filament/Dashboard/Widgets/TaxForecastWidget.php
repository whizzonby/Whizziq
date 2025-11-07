<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\TaxForecastingService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaxForecastWidget extends BaseWidget
{
    protected static ?int $sort = 11;


    public function getHeading(): string
    {
        return '🔮 Tax Forecast';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $service = app(TaxForecastingService::class);

        $forecast = $service->getDashboardForecast($user);

        return [
            Stat::make('Forecasted Annual Tax', '$' . number_format($forecast['annual']['forecasted_tax'], 2))
                ->description($forecast['annual']['months_remaining'] . ' months remaining')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-help',
                    'title' => 'Confidence: ' . ucfirst($forecast['annual']['confidence']),
                ]),

            Stat::make('Next Tax Deadline', '$' . number_format($forecast['quarterly']['estimated_payment'], 2))
                ->description('Q' . $forecast['quarterly']['quarter'] . ' - Due ' . \Carbon\Carbon::parse($forecast['quarterly']['deadline'])->format('M d'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Year Progress', round($forecast['variance']['percent_year_complete'], 1) . '%')
                ->description($forecast['variance']['tax_liability_status']['message'])
                ->descriptionIcon($forecast['variance']['tax_liability_status']['status'] === 'higher_liability' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($forecast['variance']['tax_liability_status']['status'] === 'higher_liability' ? 'danger' : 'success'),
        ];
    }
}
