<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\TaxCalculationService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TaxCategoryBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Tax Deductions by Category (YTD)';

    protected static ?int $sort = 2;


    protected function getData(): array
    {
        $user = auth()->user();
        $taxService = app(TaxCalculationService::class);

        // Get deductions breakdown for current year
        $startDate = Carbon::create(now()->year, 1, 1);
        $endDate = now();

        $deductions = $taxService->getDeductionsByCategory($user, $startDate, $endDate);

        if (empty($deductions)) {
            return [
                'datasets' => [
                    [
                        'data' => [],
                        'backgroundColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        $labels = array_column($deductions, 'category_name');
        $data = array_column($deductions, 'deductible_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Deductible Amount',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(99, 102, 241, 0.8)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
