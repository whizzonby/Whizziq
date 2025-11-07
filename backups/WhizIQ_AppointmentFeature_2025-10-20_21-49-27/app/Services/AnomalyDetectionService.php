<?php

namespace App\Services;

use App\Models\BusinessMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionService
{
    protected OpenAIService $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Detect anomalies in business metrics
     */
    public function detectMetricAnomalies(int $userId): array
    {
        $anomalies = [];

        // Get historical data
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $metrics = BusinessMetric::where('user_id', $userId)
            ->where('date', '>=', $thirtyDaysAgo)
            ->orderBy('date', 'asc')
            ->get();

        if ($metrics->count() < 7) {
            return []; // Not enough data
        }

        // Get latest metric
        $latest = $metrics->last();

        // Statistical anomaly detection
        $anomalies = array_merge($anomalies, $this->detectStatisticalAnomalies($metrics, $latest));

        // AI-powered anomaly detection (if API key is configured)
        if (!empty(config('services.openai.key'))) {
            try {
                $aiAnomalies = $this->detectAIAnomalies($metrics, $latest);
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
    protected function detectStatisticalAnomalies(Collection $metrics, BusinessMetric $latest): array
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
    protected function detectAIAnomalies(Collection $metrics, BusinessMetric $latest): ?array
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
