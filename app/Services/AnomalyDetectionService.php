<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionService
{
    protected OpenAIService $openAI;
    protected FinancialMetricsCalculator $calculator;

    public function __construct(OpenAIService $openAI, FinancialMetricsCalculator $calculator)
    {
        $this->openAI = $openAI;
        $this->calculator = $calculator;
    }

    /**
     * Detect anomalies in business metrics
     * Optimized to use BusinessMetric table instead of recalculating 30 days
     */
    public function detectMetricAnomalies(int $userId): array
    {
        $anomalies = [];

        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        // Use BusinessMetric table for fast retrieval instead of 30 separate calculations
        $startDate = Carbon::today()->subDays(30);
        $businessMetrics = \App\Models\BusinessMetric::where('user_id', $userId)
            ->where('date', '>=', $startDate)
            ->orderBy('date', 'asc')
            ->get();

        // If BusinessMetric data is insufficient, fall back to calculator (but cache the result)
        if ($businessMetrics->count() < 7) {
            // Fallback: use calculator but only for missing dates
            $metrics = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $existingMetric = $businessMetrics->firstWhere('date', $date->toDateString());
                
                if ($existingMetric) {
                    $metrics[] = (object) [
                        'date' => $date,
                        'revenue' => (float) $existingMetric->revenue,
                        'profit' => (float) $existingMetric->profit,
                        'expenses' => (float) $existingMetric->expenses,
                        'cash_flow' => (float) $existingMetric->cash_flow,
                    ];
                } else {
                    // Only calculate if missing from BusinessMetric
                    $dayMetrics = $this->calculator->calculateMetricsForPeriod($user, $date->copy()->startOfDay(), $date->copy()->endOfDay());
                    $metrics[] = (object) [
                        'date' => $date,
                        'revenue' => $dayMetrics['revenue'],
                        'profit' => $dayMetrics['profit'],
                        'expenses' => $dayMetrics['expenses'],
                        'cash_flow' => $dayMetrics['cash_flow'],
                    ];
                }
            }
            $metricsCollection = collect($metrics);
        } else {
            // Use BusinessMetric data directly - much faster!
            $metricsCollection = $businessMetrics->map(function ($metric) {
                return (object) [
                    'date' => $metric->date,
                    'revenue' => (float) $metric->revenue,
                    'profit' => (float) $metric->profit,
                    'expenses' => (float) $metric->expenses,
                    'cash_flow' => (float) $metric->cash_flow,
                ];
            });
        }

        if ($metricsCollection->count() < 7) {
            return []; // Not enough data
        }

        // Get latest metric
        $latest = $metricsCollection->last();

        // Statistical anomaly detection
        $anomalies = array_merge($anomalies, $this->detectStatisticalAnomalies($metricsCollection, $latest));

        // AI-powered anomaly detection (if API key is configured)
        if (!empty(config('services.openai.key'))) {
            try {
                $aiAnomalies = $this->detectAIAnomalies($metricsCollection, $latest);
                if ($aiAnomalies) {
                    $anomalies = array_merge($anomalies, $aiAnomalies);
                }
            } catch (\Exception $e) {
                Log::warning('AI anomaly detection failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->deduplicateAnomalies($anomalies);
    }

    /**
     * Detect anomalies using statistical methods (Z-score)
     */
    protected function detectStatisticalAnomalies(Collection $metrics, object $latest): array
    {
        $anomalies = [];
        $threshold = 2.0; // Z-score threshold

        // Check each metric
        foreach (['revenue', 'profit', 'expenses', 'cash_flow'] as $field) {
            $values = $metrics->pluck($field)->toArray();
            $mean = array_sum($values) / count($values);
            $stdDev = $this->calculateStdDev($values, $mean);

            if ($stdDev > 0) {
                $zScore = ($latest->$field - $mean) / $stdDev;

                if (abs($zScore) > $threshold) {
                    $anomalies[] = [
                        'metric' => ucfirst(str_replace('_', ' ', $field)),
                        'severity' => abs($zScore) > 3 ? 'high' : 'medium',
                        'description' => $this->getAnomalyDescription($field, $latest->$field, $mean, $zScore),
                        'recommendation' => $this->getRecommendation($field, $zScore),
                        'type' => 'statistical',
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies using AI
     */
    protected function detectAIAnomalies(Collection $metrics, object $latest): ?array
    {
        $historicalData = $metrics->take(-7)->map(fn($m) => [
            'date' => $m->date->format('Y-m-d'),
            'revenue' => $m->revenue,
            'profit' => $m->profit,
            'expenses' => $m->expenses,
            'cash_flow' => $m->cash_flow,
        ])->toArray();

        $currentData = [
            'date' => $latest->date->format('Y-m-d'),
            'revenue' => $latest->revenue,
            'profit' => $latest->profit,
            'expenses' => $latest->expenses,
            'cash_flow' => $latest->cash_flow,
        ];

        return $this->openAI->detectAnomalies($historicalData, $currentData);
    }

    /**
     * Calculate standard deviation
     */
    protected function calculateStdDev(array $values, float $mean): float
    {
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    /**
     * Get anomaly description
     */
    protected function getAnomalyDescription(string $field, float $current, float $mean, float $zScore): string
    {
        $direction = $zScore > 0 ? 'higher' : 'lower';
        $percentage = abs((($current - $mean) / $mean) * 100);

        return sprintf(
            'Current %s ($%s) is %.1f%% %s than the 30-day average ($%s).',
            str_replace('_', ' ', $field),
            number_format($current, 2),
            $percentage,
            $direction,
            number_format($mean, 2)
        );
    }

    /**
     * Get recommendation based on anomaly
     */
    protected function getRecommendation(string $field, float $zScore): string
    {
        if ($field === 'revenue' && $zScore < 0) {
            return 'Review sales performance and marketing effectiveness. Consider launching promotional campaigns.';
        }

        if ($field === 'expenses' && $zScore > 0) {
            return 'Investigate the spike in expenses. Review recent purchases and identify areas for cost reduction.';
        }

        if ($field === 'cash_flow' && $zScore < 0) {
            return 'Monitor cash flow closely. Consider accelerating receivables or negotiating better payment terms.';
        }

        if ($field === 'profit' && $zScore < 0) {
            return 'Analyze profit margins. Review pricing strategy and operational efficiency.';
        }

        return 'Monitor this trend closely and take corrective action if it continues.';
    }

    /**
     * Remove duplicate anomalies
     */
    protected function deduplicateAnomalies(array $anomalies): array
    {
        $unique = [];
        $seen = [];

        foreach ($anomalies as $anomaly) {
            $key = $anomaly['metric'] . '_' . $anomaly['severity'];

            if (!isset($seen[$key])) {
                $unique[] = $anomaly;
                $seen[$key] = true;
            }
        }

        return $unique;
    }
}
