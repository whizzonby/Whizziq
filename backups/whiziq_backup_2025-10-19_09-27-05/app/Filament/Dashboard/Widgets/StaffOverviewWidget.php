<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\StaffMetric;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $user = auth()->user();
        $latestMetric = StaffMetric::where('user_id', $user->id)
            ->latest('date')
            ->first();

        if (!$latestMetric) {
            return [
                Stat::make('Total Employees', '0')
                    ->description('No data available')
                    ->icon('heroicon-o-user-group')
                    ->color('gray'),
                Stat::make('Churn Rate', '0%')
                    ->description('No data available')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('Total Employees', number_format($latestMetric->total_employees))
                ->description('Staff count')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Churn Rate', $latestMetric->churn_rate . '%')
                ->description($this->getChurnDescription($latestMetric->churn_rate))
                ->icon('heroicon-o-arrow-path')
                ->color($this->getChurnColor($latestMetric->churn_rate)),

            Stat::make('Employee Turnover', ($latestMetric->employee_turnover ?? 0) . '%')
                ->description('Turnover rate')
                ->icon('heroicon-o-arrow-trending-down')
                ->color($this->getTurnoverColor($latestMetric->employee_turnover ?? 0)),
        ];
    }

    protected function getChurnDescription(float $churnRate): string
    {
        if ($churnRate < 5) {
            return 'Excellent retention';
        } elseif ($churnRate < 10) {
            return 'Good retention';
        } elseif ($churnRate < 15) {
            return 'Moderate churn';
        } else {
            return 'High churn - needs attention';
        }
    }

    protected function getChurnColor(float $churnRate): string
    {
        if ($churnRate < 5) {
            return 'success';
        } elseif ($churnRate < 10) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getTurnoverColor(float $turnoverRate): string
    {
        if ($turnoverRate < 10) {
            return 'success';
        } elseif ($turnoverRate < 20) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}


