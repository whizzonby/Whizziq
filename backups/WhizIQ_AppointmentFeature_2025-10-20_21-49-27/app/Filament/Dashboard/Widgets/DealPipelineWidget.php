<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Deal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DealPipelineWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $userId = Auth::id();

        // Pipeline stages
        $leadCount = Deal::where('user_id', $userId)->where('stage', 'lead')->count();
        $leadValue = Deal::where('user_id', $userId)->where('stage', 'lead')->sum('value');

        $qualifiedCount = Deal::where('user_id', $userId)->where('stage', 'qualified')->count();
        $qualifiedValue = Deal::where('user_id', $userId)->where('stage', 'qualified')->sum('value');

        $proposalCount = Deal::where('user_id', $userId)->where('stage', 'proposal')->count();
        $proposalValue = Deal::where('user_id', $userId)->where('stage', 'proposal')->sum('value');

        $negotiationCount = Deal::where('user_id', $userId)->where('stage', 'negotiation')->count();
        $negotiationValue = Deal::where('user_id', $userId)->where('stage', 'negotiation')->sum('value');

        // Total pipeline value (weighted)
        $totalWeightedValue = Deal::where('user_id', $userId)
            ->whereIn('stage', ['lead', 'qualified', 'proposal', 'negotiation'])
            ->get()
            ->sum('weighted_value');

        // Won deals this month
        $wonThisMonth = Deal::where('user_id', $userId)
            ->where('stage', 'won')
            ->whereBetween('actual_close_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('value');

        return [
            Stat::make('Pipeline Value', '$' . number_format($totalWeightedValue, 0))
                ->description('Weighted value of open deals')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart($this->getPipelineChart()),

            Stat::make('Lead', '$' . number_format($leadValue, 0))
                ->description("{$leadCount} deals")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('secondary')
                ->url(route('filament.dashboard.resources.deals.index', ['tab' => 'lead'])),

            Stat::make('Qualified', '$' . number_format($qualifiedValue, 0))
                ->description("{$qualifiedCount} deals")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->url(route('filament.dashboard.resources.deals.index', ['tab' => 'qualified'])),

            Stat::make('Proposal', '$' . number_format($proposalValue, 0))
                ->description("{$proposalCount} deals")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->url(route('filament.dashboard.resources.deals.index', ['tab' => 'proposal'])),

            Stat::make('Negotiation', '$' . number_format($negotiationValue, 0))
                ->description("{$negotiationCount} deals")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('purple')
                ->url(route('filament.dashboard.resources.deals.index', ['tab' => 'negotiation'])),

            Stat::make('Won This Month', '$' . number_format($wonThisMonth, 0))
                ->description('Closed deals')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success')
                ->url(route('filament.dashboard.resources.deals.index', ['tab' => 'won'])),
        ];
    }

    protected function getPipelineChart(): array
    {
        // Get last 7 days of won deals
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $value = Deal::where('user_id', Auth::id())
                ->where('stage', 'won')
                ->whereDate('actual_close_date', $date)
                ->sum('value');
            $data[] = (float) $value;
        }

        return $data;
    }
}
