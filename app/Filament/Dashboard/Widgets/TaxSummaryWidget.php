<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\TaxCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaxSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 10;


    public function getHeading(): string
    {
        return '🧾 Tax Summary (Year to Date)';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $taxService = app(TaxCalculationService::class);

        // Get year-to-date summary
        $summary = $taxService->getYearToDateSummary($user);

        return [
            Stat::make('Total Revenue (YTD)', '$' . number_format($summary['total_revenue'], 2))
                ->description('Year to date')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Total Deductions (YTD)', '$' . number_format($summary['total_deductions'], 2))
                ->description('Tax deductible expenses')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),

            Stat::make('Taxable Income (YTD)', '$' . number_format($summary['taxable_income'], 2))
                ->description('Revenue minus deductions')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Estimated Tax Owed', '$' . number_format($summary['estimated_tax'], 2))
                ->description($summary['effective_tax_rate'] . '% effective rate')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
        ];
    }
}
