<?php

namespace App\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Widgets\AIBusinessInsightsWidget;
use App\Filament\Dashboard\Widgets\AIFinancialForecastWidget;
use App\Filament\Dashboard\Widgets\AnomalyDetectionWidget;
use App\Filament\Dashboard\Widgets\BusinessPerformanceTrendWidget;
use App\Filament\Dashboard\Widgets\CashFlowChartWidget;
use App\Filament\Dashboard\Widgets\CashFlowSummaryWidget;
use App\Filament\Dashboard\Widgets\ExpenseBreakdownWidget;
use App\Filament\Dashboard\Widgets\ExpenseInsightsWidget;
use App\Filament\Dashboard\Widgets\NaturalLanguageQueryWidget;
use App\Filament\Dashboard\Widgets\ProfitabilityRatiosWidget;
use App\Filament\Dashboard\Widgets\RevenueInsightsWidget;
use App\Filament\Dashboard\Widgets\RevenueSourcesWidget;
use App\Filament\Dashboard\Widgets\RevenueVsExpenseChartWidget;
use App\Filament\Dashboard\Widgets\RiskAssessmentWidget;
use App\Filament\Dashboard\Widgets\SwotAnalysisDashboardWidget;
use Filament\Pages\Page;
use BackedEnum;

class AnalyticsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Business Analytics Dashboard';

    protected static ?int $navigationSort = 1;
    
    protected static bool $shouldRegisterNavigation = true;

    protected string $view = 'filament.dashboard.pages.analytics-dashboard';


    protected function getHeaderWidgets(): array
    {
        return [
            // Comprehensive AI-Powered Business Insights (Top Priority)
            AIBusinessInsightsWidget::class,
            // Financial KPI Summary
            CashFlowSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Core Financial Analytics
            RevenueVsExpenseChartWidget::class,
            RevenueSourcesWidget::class,
            RevenueInsightsWidget::class,
            ExpenseBreakdownWidget::class,
            ExpenseInsightsWidget::class,
            ProfitabilityRatiosWidget::class,
            CashFlowChartWidget::class,
            BusinessPerformanceTrendWidget::class,

            // AI-Powered Predictive Analytics
            AIFinancialForecastWidget::class,
            AnomalyDetectionWidget::class,

            // Business Risk & Intelligence
            RiskAssessmentWidget::class,
            SwotAnalysisDashboardWidget::class,
            NaturalLanguageQueryWidget::class,
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
