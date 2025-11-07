<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\TaxOptimizationService;
use Filament\Widgets\Widget;

class TaxOptimizationWidget extends Widget
{
    protected static ?int $sort = 11;


    protected string $view = 'filament.dashboard.widgets.tax-optimization-widget';

    protected int | string | array $columnSpan = 'full';

    public function getOptimizations(): array
    {
        $user = auth()->user();
        $service = app(TaxOptimizationService::class);

        return $service->generateOptimizationRecommendations($user);
    }
}
