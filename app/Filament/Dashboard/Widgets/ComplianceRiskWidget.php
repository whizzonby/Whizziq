<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\ComplianceMonitoringService;
use Filament\Widgets\Widget;

class ComplianceRiskWidget extends Widget
{
    protected static ?int $sort = 10;


    protected string $view = 'filament.dashboard.widgets.compliance-risk-widget';

    protected int | string | array $columnSpan = 'full';

    public function getRiskAssessment(): array
    {
        $user = auth()->user();
        $service = app(ComplianceMonitoringService::class);

        return $service->calculateComplianceRiskScore($user);
    }
}
