<?php

namespace App\Filament\Dashboard\Pages;

use App\Services\RevenueForecastService;
use BackedEnum;
use Filament\Pages\Page;

class RevenueForecastPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.dashboard.pages.revenue-forecast-page';

    protected static ?string $navigationLabel = 'Revenue Forecast';

    public static function getNavigationGroup(): ?string
    {
        return 'CRM';
    }

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Revenue Forecast';


    public array $monthlyForecast = [];
    public array $quarterlyForecast = [];
    public array $winRateStats = [];
    public array $stageDistribution = [];
    public $topDeals;
    public ?int $avgCycleTime = null;

    public function mount(): void
    {
        $service = app(RevenueForecastService::class);

        $this->monthlyForecast = $service->getForecast(auth()->id(), 6);
        $this->quarterlyForecast = $service->getQuarterlyForecast(auth()->id());
        $this->winRateStats = $service->getWinRateStats(auth()->id());
        $this->stageDistribution = $service->getStageDistribution(auth()->id());
        $this->topDeals = $service->getTopForecastDeals(auth()->id(), 10);
        $this->avgCycleTime = $service->getAverageCycleTime(auth()->id());
    }

    public function getHeading(): string
    {
        return 'Revenue Forecast & Predictions';
    }

    public function getSubheading(): ?string
    {
        return 'Forecast future revenue based on pipeline probability and historical data';
    }
}
