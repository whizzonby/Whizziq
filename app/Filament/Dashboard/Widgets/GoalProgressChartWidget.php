<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Goal;
use Filament\Widgets\ChartWidget;

class GoalProgressChartWidget extends ChartWidget
{
    protected static ?int $sort = 21;


    protected ?string $heading = '📈 Goals Progress Overview';

    protected ?string $description = 'Visual breakdown of all active goals';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    protected function getData(): array
    {
        $goals = Goal::where('user_id', auth()->id())
            ->active()
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($goals as $goal) {
            $labels[] = \Illuminate\Support\Str::limit($goal->title, 30);
            $data[] = $goal->progress_percentage;

            // Color based on progress
            if ($goal->progress_percentage >= 75) {
                $colors[] = 'rgb(34, 197, 94)'; // Green
            } elseif ($goal->progress_percentage >= 50) {
                $colors[] = 'rgb(59, 130, 246)'; // Blue
            } elseif ($goal->progress_percentage >= 25) {
                $colors[] = 'rgb(251, 146, 60)'; // Orange
            } else {
                $colors[] = 'rgb(239, 68, 68)'; // Red
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Progress (%)',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
