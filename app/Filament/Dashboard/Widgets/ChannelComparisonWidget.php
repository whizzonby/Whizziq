<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class ChannelComparisonWidget extends Widget
{
    protected static ?string $heading = '📱 Marketing Channel Comparison';

    protected static ?int $sort = 29;

    /**
     * Check if user can view the widget
     * Marketing Analytics is a Pro+ feature (analytics_marketing)
     */

    protected string $view = 'filament.dashboard.widgets.channel-comparison-widget';

    protected int | string | array $columnSpan = 'full';

    public function getChannelData(): array
    {
        $user = auth()->user();
        $today = Carbon::today();

        $channels = ['facebook', 'google', 'linkedin'];
        $channelData = [];

        foreach ($channels as $channel) {
            $metric = MarketingMetric::where('user_id', $user->id)
                ->where('date', $today)
                ->where('channel', $channel)
                ->first();

            if ($metric) {
                $channelData[$channel] = [
                    'name' => ucfirst($channel),
                    'conversions' => $metric->conversions,
                    'cost_per_conversion' => $metric->cost_per_conversion,
                    'reach' => $metric->reach,
                    'ad_spend' => $metric->ad_spend,
                    'roi' => $metric->roi,
                    'clicks' => $metric->clicks,
                    'conversion_rate' => $metric->conversion_rate,
                    'cpc' => $metric->cost_per_click,
                    'impressions' => $metric->impressions,
                ];
            } else {
                $channelData[$channel] = [
                    'name' => ucfirst($channel),
                    'conversions' => 0,
                    'cost_per_conversion' => 0,
                    'reach' => 0,
                    'ad_spend' => 0,
                    'roi' => 0,
                    'clicks' => 0,
                    'conversion_rate' => 0,
                    'cpc' => 0,
                    'impressions' => 0,
                ];
            }
        }

        return $channelData;
    }

    public function getBestPerformingChannel(): ?string
    {
        $channelData = $this->getChannelData();

        $bestROI = null;
        $bestChannel = null;

        foreach ($channelData as $channel => $data) {
            if ($data['roi'] > ($bestROI ?? 0)) {
                $bestROI = $data['roi'];
                $bestChannel = $data['name'];
            }
        }

        return $bestChannel;
    }

    public function getLowestCPCChannel(): ?string
    {
        $channelData = $this->getChannelData();

        $lowestCPC = PHP_FLOAT_MAX;
        $lowestChannel = null;

        foreach ($channelData as $channel => $data) {
            if ($data['cpc'] > 0 && $data['cpc'] < $lowestCPC) {
                $lowestCPC = $data['cpc'];
                $lowestChannel = $data['name'];
            }
        }

        return $lowestChannel;
    }

    public function getChannelIcon(string $channel): string
    {
        return match(strtolower($channel)) {
            'facebook' => 'heroicon-o-chat-bubble-left-right',
            'google' => 'heroicon-o-magnifying-glass',
            'linkedin' => 'heroicon-o-briefcase',
            default => 'heroicon-o-globe-alt',
        };
    }

    public function getChannelColor(string $channel): string
    {
        return match(strtolower($channel)) {
            'facebook' => 'primary',
            'google' => 'success',
            'linkedin' => 'info',
            default => 'gray',
        };
    }
}
