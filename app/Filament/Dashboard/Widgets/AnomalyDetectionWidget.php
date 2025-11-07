<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\AnomalyDetectionService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AnomalyDetectionWidget extends Widget
{
    protected static ?string $heading = '🔍 Anomaly Detection';

    protected static ?int $sort = 17;

    protected string $view = 'filament.dashboard.widgets.anomaly-detection-widget';

    protected int | string | array $columnSpan = 'full';

    // Enable lazy loading to prevent blocking dashboard load
    protected static bool $isLazy = true;

    public ?array $anomalies = null;

    public bool $isLoading = false;

    public function mount()
    {
        // Don't load immediately - use lazy loading to prevent blocking dashboard
        $this->loadAnomalies();
    }

    public function loadAnomalies()
    {
        $this->isLoading = true;

        try {
            $user = auth()->user();
            $cacheKey = "anomaly_detection_{$user->id}_" . now()->format('Y-m-d-H');

            // Cache for 2 hours to prevent duplicate AI calls on page reload and reduce API costs
            $this->anomalies = Cache::remember($cacheKey, 7200, function () use ($user) {
                $service = app(AnomalyDetectionService::class);
                return $service->detectMetricAnomalies($user->id);
            });
        } catch (\Exception $e) {
            $this->anomalies = [];
        } finally {
            $this->isLoading = false;
        }
    }

    public function detectAnomalies()
    {
        // Alias for backward compatibility
        $this->loadAnomalies();
    }

    public function refreshAnomalies()
    {
        // Clear cache and regenerate
        $user = auth()->user();
        $cacheKey = "anomaly_detection_{$user->id}_" . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);
        
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
