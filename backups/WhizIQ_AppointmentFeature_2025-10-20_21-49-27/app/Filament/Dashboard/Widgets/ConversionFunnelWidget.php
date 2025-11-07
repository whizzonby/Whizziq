<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class ConversionFunnelWidget extends Widget
{
    protected static ?int $sort = 11;

    protected string $view = 'filament.dashboard.widgets.conversion-funnel-widget';

    protected int | string | array $columnSpan = 'full';

    public function getFunnelData(): array
    {
        $user = auth()->user();
        $today = Carbon::today();

        // Aggregate all channels for today
        $metrics = MarketingMetric::where('user_id', $user->id)
            ->where('date', $today)
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'awareness' => 0,
                'leads' => 0,
                'conversions' => 0,
                'retention' => 0,
                'lead_conversion_rate' => 0,
                'customer_conversion_rate' => 0,
                'retention_rate' => 0,
                'overall_conversion_rate' => 0,
            ];
        }

        $awareness = $metrics->sum('awareness');
        $leads = $metrics->sum('leads');
        $conversions = $metrics->sum('conversions');
        $retention = $metrics->sum('retention_count');

        return [
            'awareness' => $awareness,
            'leads' => $leads,
            'conversions' => $conversions,
            'retention' => $retention,
            'lead_conversion_rate' => $awareness > 0 ? round(($leads / $awareness) * 100, 2) : 0,
            'customer_conversion_rate' => $leads > 0 ? round(($conversions / $leads) * 100, 2) : 0,
            'retention_rate' => $conversions > 0 ? round(($retention / $conversions) * 100, 2) : 0,
            'overall_conversion_rate' => $awareness > 0 ? round(($conversions / $awareness) * 100, 2) : 0,
        ];
    }

    public function getFunnelStages(): array
    {
        $data = $this->getFunnelData();

        return [
            [
                'name' => 'Awareness',
                'value' => $data['awareness'],
                'percentage' => 100,
                'color' => 'info',
                'icon' => 'heroicon-o-eye',
            ],
            [
                'name' => 'Leads',
                'value' => $data['leads'],
                'percentage' => $data['lead_conversion_rate'],
                'color' => 'primary',
                'icon' => 'heroicon-o-user-group',
            ],
            [
                'name' => 'Conversions',
                'value' => $data['conversions'],
                'percentage' => $data['customer_conversion_rate'],
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ],
            [
                'name' => 'Retention',
                'value' => $data['retention'],
                'percentage' => $data['retention_rate'],
                'color' => 'warning',
                'icon' => 'heroicon-o-arrow-path',
            ],
        ];
    }
}
