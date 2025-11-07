<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class AppointmentsChartWidget extends ChartWidget
{
    protected ?string $heading = '📊 Appointments Over Time';
    protected static ?int $sort = 26;


    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '30days';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $userId = auth()->id();
        $filter = $this->filter;

        // Determine date range
        switch ($filter) {
            case '7days':
                $start = now()->subDays(7);
                $groupBy = 'day';
                break;
            case '30days':
                $start = now()->subDays(30);
                $groupBy = 'day';
                break;
            case '90days':
                $start = now()->subDays(90);
                $groupBy = 'week';
                break;
            case 'year':
                $start = now()->startOfYear();
                $groupBy = 'month';
                break;
            default:
                $start = now()->subDays(30);
                $groupBy = 'day';
        }

        $appointments = Appointment::where('user_id', $userId)
            ->where('start_datetime', '>=', $start)
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->get();

        // Group by time period
        if ($groupBy === 'day') {
            $labels = [];
            $scheduledData = [];
            $completedData = [];

            $days = $filter === '7days' ? 7 : ($filter === '30days' ? 30 : 90);

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('M d');

                $dayAppointments = $appointments->filter(function ($apt) use ($date) {
                    return $apt->start_datetime->isSameDay($date);
                });

                $scheduledData[] = $dayAppointments->whereIn('status', ['scheduled', 'confirmed'])->count();
                $completedData[] = $dayAppointments->where('status', 'completed')->count();
            }
        } elseif ($groupBy === 'week') {
            $labels = [];
            $scheduledData = [];
            $completedData = [];

            for ($i = 12; $i >= 0; $i--) {
                $weekStart = now()->subWeeks($i)->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();
                $labels[] = $weekStart->format('M d');

                $weekAppointments = $appointments->filter(function ($apt) use ($weekStart, $weekEnd) {
                    return $apt->start_datetime->between($weekStart, $weekEnd);
                });

                $scheduledData[] = $weekAppointments->whereIn('status', ['scheduled', 'confirmed'])->count();
                $completedData[] = $weekAppointments->where('status', 'completed')->count();
            }
        } else { // month
            $labels = [];
            $scheduledData = [];
            $completedData = [];

            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $labels[] = $month->format('M');

                $monthAppointments = $appointments->filter(function ($apt) use ($month) {
                    return $apt->start_datetime->isSameMonth($month);
                });

                $scheduledData[] = $monthAppointments->whereIn('status', ['scheduled', 'confirmed'])->count();
                $completedData[] = $monthAppointments->where('status', 'completed')->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Scheduled/Confirmed',
                    'data' => $scheduledData,
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#3B82F6',
                ],
                [
                    'label' => 'Completed',
                    'data' => $completedData,
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
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
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
