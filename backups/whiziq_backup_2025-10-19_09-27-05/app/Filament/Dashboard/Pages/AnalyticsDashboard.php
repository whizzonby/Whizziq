<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\AIFinancialForecastWidget;
use App\Filament\Dashboard\Widgets\AnomalyDetectionWidget;
use App\Filament\Dashboard\Widgets\AutomatedInsightsWidget;
use App\Filament\Dashboard\Widgets\CashFlowChartWidget;
use App\Filament\Dashboard\Widgets\CashFlowSummaryWidget;
use App\Filament\Dashboard\Widgets\ChannelComparisonWidget;
use App\Filament\Dashboard\Widgets\CLVvsCACWidget;
use App\Filament\Dashboard\Widgets\ConversionFunnelWidget;
use App\Filament\Dashboard\Widgets\EngagementTrafficWidget;
use App\Filament\Dashboard\Widgets\FinancialAlertBarWidget;
use App\Filament\Dashboard\Widgets\FinancialKpiWidget;
use App\Filament\Dashboard\Widgets\MarketingInsightsWidget;
use App\Filament\Dashboard\Widgets\MarketingMetricsWidget;
use App\Filament\Dashboard\Widgets\NaturalLanguageQueryWidget;
use App\Filament\Dashboard\Widgets\ProfitabilityRatiosWidget;
use App\Filament\Dashboard\Widgets\RevenueVsExpenseChartWidget;
use App\Filament\Dashboard\Widgets\RiskAssessmentWidget;
use App\Filament\Dashboard\Widgets\StaffOverviewWidget;
use App\Filament\Dashboard\Widgets\SwotAnalysisWidget;
use Filament\Pages\Page;
use BackedEnum;

class AnalyticsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Business Analytics Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.dashboard.pages.analytics-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            // Temporarily disabled problematic widgets
            // FinancialAlertBarWidget::class,
            // NaturalLanguageQueryWidget::class,
            // FinancialKpiWidget::class,
            // CashFlowSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Temporarily disabled all widgets to test
            // RevenueVsExpenseChartWidget::class,
            // ProfitabilityRatiosWidget::class,
            // AIFinancialForecastWidget::class,
            // AutomatedInsightsWidget::class,
            // AnomalyDetectionWidget::class,
            // CashFlowChartWidget::class,
            // SwotAnalysisWidget::class,
            // RiskAssessmentWidget::class,
            // StaffOverviewWidget::class,
            // MarketingInsightsWidget::class,
            // ConversionFunnelWidget::class,
            // EngagementTrafficWidget::class,
            // ChannelComparisonWidget::class,
            // CLVvsCACWidget::class,
            // MarketingMetricsWidget::class,
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
            'lg' => 3,
            'xl' => 4,
        ];
    }
}
