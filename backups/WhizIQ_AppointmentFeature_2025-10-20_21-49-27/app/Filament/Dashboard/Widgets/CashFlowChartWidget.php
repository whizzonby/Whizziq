<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\CashFlowHistory;
use Filament\Widgets\ChartWidget;

class CashFlowChartWidget extends ChartWidget
{
    protected ?string $heading = 'Cash Flow Trend';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();

        $cashFlowData = CashFlowHistory::where('user_id', $user->id)
            ->orderBy('date', 'asc')
            ->take(6)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Cash Flow',
                    'data' => $cashFlowData->pluck('amount')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $cashFlowData->pluck('month_label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                ],
            ],
        ];
    }
}
