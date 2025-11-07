<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\MarketingInsightsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

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
            $user = auth()->user();
            $cacheKey = "marketing_insights_{$user->id}_" . now()->format('Y-m-d-H');

            // Cache for 2 hours to prevent duplicate AI calls on page reload and reduce API costs
            $this->insights = Cache::remember($cacheKey, 7200, function () use ($user) {
                $service = app(MarketingInsightsService::class);
                return $service->generateMarketingInsights($user->id);
            });
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
        // Clear cache and regenerate
        $user = auth()->user();
        $cacheKey = "marketing_insights_{$user->id}_" . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);
        
        $this->generateInsights();
    }
}
