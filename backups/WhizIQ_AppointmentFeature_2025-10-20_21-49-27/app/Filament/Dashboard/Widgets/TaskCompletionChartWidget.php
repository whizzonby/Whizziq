<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TaskCompletionChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Task Completion Trend';

    protected ?string $description = 'Tasks completed over the last 7 days';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $completed = Task::where('user_id', auth()->id())
                ->where('status', 'completed')
                ->whereDate('completed_at', $date->toDateString())
                ->count();

            $data[] = $completed;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tasks Completed',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
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
