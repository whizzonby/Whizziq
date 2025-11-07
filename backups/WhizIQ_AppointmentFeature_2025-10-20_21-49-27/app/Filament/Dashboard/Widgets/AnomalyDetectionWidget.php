<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\AnomalyDetectionService;
use Filament\Widgets\Widget;

class AnomalyDetectionWidget extends Widget
{
    protected static ?int $sort = 10;

    protected string $view = 'filament.dashboard.widgets.anomaly-detection-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $anomalies = null;

    public bool $isLoading = false;

    public function mount()
    {
        $this->detectAnomalies();
    }

    public function detectAnomalies()
    {
        $this->isLoading = true;

        try {
            $service = app(AnomalyDetectionService::class);
            $this->anomalies = $service->detectMetricAnomalies(auth()->id());
        } catch (\Exception $e) {
            $this->anomalies = [];
        } finally {
            $this->isLoading = false;
        }
    }

    public function refreshAnomalies()
    {
        $this->detectAnomalies();
    }

    public function getSeverityColor(string $severity): string
    {
        return match($severity) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'gray',
        };
    }

    public function getSeverityIcon(string $severity): string
    {
        return match($severity) {
            'high' => 'heroicon-o-exclamation-circle',
            'medium' => 'heroicon-o-exclamation-triangle',
            'low' => 'heroicon-o-information-circle',
            default => 'heroicon-o-bell',
        };
    }
}
