<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\AIUsageWidget;
use App\Filament\Dashboard\Widgets\BusinessMetricsOverviewWidget;
use App\Filament\Dashboard\Widgets\FinancialKpiWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * Only show essential overview widgets on the main dashboard
     * All other widgets are organized into their respective dashboards:
     * - CRM widgets → CRMDashboard
     * - Financial/Analytics widgets → AnalyticsDashboard
     * - Marketing widgets → MarketingDashboard
     * - Task widgets → TasksDashboard
     * - Goal widgets → GoalsDashboard
     * - Tax widgets → TaxDashboardPage
     * - Appointment widgets → AppointmentAnalyticsDashboard
     */
    protected function getHeaderWidgets(): array
    {
        return [
            FinancialKpiWidget::class,
            AIUsageWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            BusinessMetricsOverviewWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 4;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 4,
        ];
    }
}

