<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\MarketingInsightsService;
use Filament\Widgets\Widget;

class MarketingInsightsWidget extends Widget
{
    protected static ?int $sort = 15;

    protected string $view = 'filament.dashboard.widgets.marketing-insights-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $insights = null;

    public bool $isLoading = false;

    public function mount()
    {
        $this->generateInsights();
    }

    public function generateInsights()
    {
        $this->isLoading = true;

        try {
            $service = app(MarketingInsightsService::class);
            $this->insights = $service->generateMarketingInsights(auth()->id());
        } catch (\Exception $e) {
            $this->insights = [
                [
                    'type' => 'warning',
                    'title' => 'Insights Generation Error',
                    'description' => 'Unable to generate marketing insights. Please check your data and try again.',
                    'icon' => 'heroicon-o-exclamation-triangle',
                ],
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    public function refreshInsights()
    {
        $this->generateInsights();
    }
}
