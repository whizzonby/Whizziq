<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class CLVvsCACWidget extends Widget
{
    protected static ?int $sort = 14;

    protected string $view = 'filament.dashboard.widgets.clv-vs-cac-widget';

    protected int | string | array $columnSpan = 'full';

    public function getCLVCACData(): array
    {
        $user = auth()->user();
        $today = Carbon::today();

        $metrics = MarketingMetric::where('user_id', $user->id)
            ->where('date', $today)
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'avg_clv' => 0,
                'avg_cac' => 0,
                'ratio' => 0,
                'health' => 'No Data',
                'health_color' => 'gray',
                'total_clv' => 0,
                'total_cac' => 0,
                'channels' => [],
            ];
        }

        $avgCLV = $metrics->avg('customer_lifetime_value');
        $avgCAC = $metrics->avg('customer_acquisition_cost');
        $totalCLV = $metrics->sum('customer_lifetime_value');
        $totalCAC = $metrics->sum('customer_acquisition_cost');
        $ratio = $avgCAC > 0 ? round($avgCLV / $avgCAC, 2) : 0;

        // Determine health status
        if ($ratio >= 3) {
            $health = 'Excellent';
            $healthColor = 'success';
        } elseif ($ratio >= 2) {
            $health = 'Good';
            $healthColor = 'primary';
        } elseif ($ratio >= 1) {
            $health = 'Acceptable';
            $healthColor = 'warning';
        } else {
            $health = 'Poor';
            $healthColor = 'danger';
        }

        // Channel breakdown
        $channels = [];
        foreach ($metrics as $metric) {
            $channels[] = [
                'name' => $metric->channel_name,
                'clv' => $metric->customer_lifetime_value,
                'cac' => $metric->customer_acquisition_cost,
                'ratio' => $metric->clv_cac_ratio,
                'health' => $metric->clv_cac_health,
            ];
        }

        return [
            'avg_clv' => $avgCLV,
            'avg_cac' => $avgCAC,
            'ratio' => $ratio,
            'health' => $health,
            'health_color' => $healthColor,
            'total_clv' => $totalCLV,
            'total_cac' => $totalCAC,
            'channels' => $channels,
        ];
    }

    public function getRecommendation(): string
    {
        $data = $this->getCLVCACData();

        if ($data['ratio'] >= 3) {
            return "Excellent! Your customer economics are strong. Consider scaling your marketing spend to acquire more customers.";
        } elseif ($data['ratio'] >= 2) {
            return "Good ratio. Focus on increasing customer lifetime value through upsells and retention strategies.";
        } elseif ($data['ratio'] >= 1) {
            return "Acceptable, but needs improvement. Work on reducing acquisition costs or increasing customer value.";
        } else {
            return "Critical: You're spending more to acquire customers than they're worth. Urgently review marketing efficiency.";
        }
    }

    public function getCLVPercentage(): int
    {
        $data = $this->getCLVCACData();
        $total = $data['avg_clv'] + $data['avg_cac'];

        return $total > 0 ? round(($data['avg_clv'] / $total) * 100) : 50;
    }

    public function getCACPercentage(): int
    {
        return 100 - $this->getCLVPercentage();
    }
}
