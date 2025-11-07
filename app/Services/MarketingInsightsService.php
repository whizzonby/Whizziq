<?php

namespace App\Services;

use App\Models\MarketingMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarketingInsightsService
{
    protected OpenAIService $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    /**
     * Generate comprehensive marketing insights
     */
    public function generateMarketingInsights(int $userId): array
    {
        $today = Carbon::today();

        // Get today's metrics for all channels
        $channelMetrics = MarketingMetric::where('user_id', $userId)
            ->where('date', $today)
            ->get();

        if ($channelMetrics->isEmpty()) {
            return $this->getDefaultInsights();
        }

        $insights = [];

        // 1. Channel Performance Comparison Insights
        $insights = array_merge($insights, $this->generateChannelComparisonInsights($channelMetrics));

        // 2. Conversion Funnel Insights
        $insights = array_merge($insights, $this->generateFunnelInsights($channelMetrics));

        // 3. CLV:CAC Health Insights
        $insights = array_merge($insights, $this->generateCLVCACInsights($channelMetrics));

        // 4. ROI Insights
        $insights = array_merge($insights, $this->generateROIInsights($channelMetrics));

        // 5. AI-powered insights (if API key is configured)
        if (!empty(config('services.openai.key'))) {
            try {
                $aiInsights = $this->generateAIMarketingInsights($channelMetrics);
                if ($aiInsights) {
                    $insights = array_merge($insights, $aiInsights);
                }
            } catch (\Exception $e) {
                Log::warning('AI marketing insights failed', ['error' => $e->getMessage()]);
            }
        }

        return $insights;
    }

    /**
     * Generate channel comparison insights
     */
    protected function generateChannelComparisonInsights($metrics): array
    {
        $insights = [];

        // Find best and worst performing channels
        $bestROI = $metrics->sortByDesc('roi')->first();
        $lowestCPC = $metrics->where('cost_per_click', '>', 0)->sortBy('cost_per_click')->first();
        $lowestCostPerConversion = $metrics->where('cost_per_conversion', '>', 0)->sortBy('cost_per_conversion')->first();

        if ($bestROI && $bestROI->roi > 0) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Best Performing Channel: ' . $bestROI->channel_name,
                'description' => sprintf(
                    '%s is delivering the highest ROI at %.1f%%. Current conversions: %d with $%.2f cost per conversion.',
                    $bestROI->channel_name,
                    $bestROI->roi,
                    $bestROI->conversions,
                    $bestROI->cost_per_conversion
                ),
                'icon' => 'heroicon-o-trophy',
            ];
        }

        if ($lowestCostPerConversion && $lowestCostPerConversion->channel !== $bestROI?->channel) {
            $reachDiff = $bestROI ? (($lowestCostPerConversion->reach / max($bestROI->reach, 1)) * 100) : 100;
            $costDiff = $bestROI ? ((($bestROI->cost_per_conversion - $lowestCostPerConversion->cost_per_conversion) / max($bestROI->cost_per_conversion, 1)) * 100) : 0;

            $insights[] = [
                'type' => 'info',
                'title' => sprintf('%s Has Lower Acquisition Costs', $lowestCostPerConversion->channel_name),
                'description' => sprintf(
                    '%s ads had %.0f%% lower cost per conversion ($%.2f vs $%.2f) but %.0f%% of the audience reach — test scaling with adjusted budget allocation.',
                    $lowestCostPerConversion->channel_name,
                    abs($costDiff),
                    $lowestCostPerConversion->cost_per_conversion,
                    $bestROI?->cost_per_conversion ?? 0,
                    $reachDiff
                ),
                'icon' => 'heroicon-o-currency-dollar',
            ];
        }

        return $insights;
    }

    /**
     * Generate funnel-related insights
     */
    protected function generateFunnelInsights($metrics): array
    {
        $insights = [];

        $totalAwareness = $metrics->sum('awareness');
        $totalLeads = $metrics->sum('leads');
        $totalConversions = $metrics->sum('conversions');
        $totalRetention = $metrics->sum('retention_count');

        // Lead conversion rate
        $leadConversionRate = $totalAwareness > 0 ? ($totalLeads / $totalAwareness) * 100 : 0;

        if ($leadConversionRate < 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Lead Conversion Rate',
                'description' => sprintf(
                    'Only %.1f%% of awareness is converting to leads. Consider improving ad targeting, landing page optimization, or lead magnet offers.',
                    $leadConversionRate
                ),
                'icon' => 'heroicon-o-exclamation-triangle',
            ];
        } elseif ($leadConversionRate > 20) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong Lead Generation',
                'description' => sprintf(
                    'Excellent %.1f%% conversion from awareness to leads. Your targeting and messaging are resonating well with your audience.',
                    $leadConversionRate
                ),
                'icon' => 'heroicon-o-check-circle',
            ];
        }

        // Customer conversion rate
        $customerConversionRate = $totalLeads > 0 ? ($totalConversions / $totalLeads) * 100 : 0;

        if ($customerConversionRate < 2) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Sales Funnel Bottleneck',
                'description' => sprintf(
                    'Only %.1f%% of leads are converting to customers. Review your sales process, pricing, and value proposition.',
                    $customerConversionRate
                ),
                'icon' => 'heroicon-o-funnel',
            ];
        }

        // Retention insights
        $retentionRate = $totalConversions > 0 ? ($totalRetention / $totalConversions) * 100 : 0;

        if ($retentionRate > 60) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Customer Retention',
                'description' => sprintf(
                    '%.1f%% retention rate indicates strong product-market fit and customer satisfaction. Focus on referral programs to leverage happy customers.',
                    $retentionRate
                ),
                'icon' => 'heroicon-o-arrow-path',
            ];
        } elseif ($retentionRate < 30) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Retention Needs Attention',
                'description' => sprintf(
                    'Only %.1f%% of customers are being retained. Implement customer success programs, loyalty rewards, and regular engagement campaigns.',
                    $retentionRate
                ),
                'icon' => 'heroicon-o-arrow-path',
            ];
        }

        return $insights;
    }

    /**
     * Generate CLV:CAC insights
     */
    protected function generateCLVCACInsights($metrics): array
    {
        $insights = [];

        foreach ($metrics as $metric) {
            if ($metric->clv_cac_ratio < 1) {
                $insights[] = [
                    'type' => 'danger',
                    'title' => sprintf('Critical: %s CAC Exceeds CLV', $metric->channel_name),
                    'description' => sprintf(
                        '%s CLV:CAC ratio is %.2f:1. You\'re spending $%.2f to acquire customers worth $%.2f. Pause or optimize this channel immediately.',
                        $metric->channel_name,
                        $metric->clv_cac_ratio,
                        $metric->customer_acquisition_cost,
                        $metric->customer_lifetime_value
                    ),
                    'icon' => 'heroicon-o-exclamation-circle',
                ];
            } elseif ($metric->clv_cac_ratio >= 3) {
                $insights[] = [
                    'type' => 'success',
                    'title' => sprintf('%s: Excellent Unit Economics', $metric->channel_name),
                    'description' => sprintf(
                        '%s has a strong %.2f:1 CLV:CAC ratio. This channel is highly profitable — consider increasing budget allocation here.',
                        $metric->channel_name,
                        $metric->clv_cac_ratio
                    ),
                    'icon' => 'heroicon-o-check-badge',
                ];
            }
        }

        return $insights;
    }

    /**
     * Generate ROI insights
     */
    protected function generateROIInsights($metrics): array
    {
        $insights = [];

        $totalAdSpend = $metrics->sum('ad_spend');
        $avgROI = $metrics->avg('roi');

        if ($avgROI > 200) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Outstanding Marketing ROI',
                'description' => sprintf(
                    'Average ROI of %.1f%% across all channels. You\'re generating $%.2f for every $1 spent. Strong performance!',
                    $avgROI,
                    $avgROI / 100 + 1
                ),
                'icon' => 'heroicon-o-chart-bar',
            ];
        } elseif ($avgROI < 50) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Marketing ROI',
                'description' => sprintf(
                    'Average ROI of %.1f%% is below target. With $%.2f in ad spend, review targeting, creative performance, and landing page effectiveness.',
                    $avgROI,
                    $totalAdSpend
                ),
                'icon' => 'heroicon-o-exclamation-triangle',
            ];
        }

        return $insights;
    }

    /**
     * Generate AI-powered marketing insights
     */
    protected function generateAIMarketingInsights($metrics): ?array
    {
        $marketingData = [
            'channels' => $metrics->map(fn($m) => [
                'channel' => $m->channel,
                'awareness' => $m->awareness,
                'leads' => $m->leads,
                'conversions' => $m->conversions,
                'retention' => $m->retention_count,
                'ad_spend' => $m->ad_spend,
                'roi' => $m->roi,
                'cost_per_conversion' => $m->cost_per_conversion,
                'reach' => $m->reach,
                'clv' => $m->customer_lifetime_value,
                'cac' => $m->customer_acquisition_cost,
                'clv_cac_ratio' => $m->clv_cac_ratio,
            ])->toArray(),
        ];

        $response = $this->openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a marketing analytics expert. Analyze marketing channel data and provide specific, actionable insights. Focus on identifying opportunities for optimization, budget reallocation, and performance improvements. Be concise and data-driven.',
            ],
            [
                'role' => 'user',
                'content' => "Analyze this marketing data and provide 2-3 actionable insights:\n\n" . json_encode($marketingData, JSON_PRETTY_PRINT),
            ],
        ], [
            'max_tokens' => 500,
        ]);

        if ($response) {
            return $this->parseAIInsights($response);
        }

        return null;
    }

    /**
     * Parse AI response into structured insights
     */
    protected function parseAIInsights(string $response): array
    {
        $lines = explode("\n", trim($response));
        $insights = [];
        $currentInsight = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if it's a numbered or bulleted item (start of new insight)
            if (preg_match('/^(\d+\.|[-*•])\s*(.+)$/', $line, $matches)) {
                if ($currentInsight) {
                    $insights[] = $currentInsight;
                }

                $title = $matches[2];
                $currentInsight = [
                    'type' => 'info',
                    'title' => $title,
                    'description' => '',
                    'icon' => 'heroicon-o-sparkles',
                ];
            } elseif ($currentInsight) {
                $currentInsight['description'] .= $line . ' ';
            }
        }

        if ($currentInsight) {
            $insights[] = $currentInsight;
        }

        return !empty($insights) ? $insights : [[
            'type' => 'info',
            'title' => 'AI Analysis',
            'description' => $response,
            'icon' => 'heroicon-o-sparkles',
        ]];
    }

    /**
     * Get default insights when no data is available
     */
    protected function getDefaultInsights(): array
    {
        return [
            [
                'type' => 'info',
                'title' => 'Start Tracking Marketing Metrics',
                'description' => 'Add marketing data for different channels to get AI-powered insights on channel performance, conversion funnel optimization, and ROI analysis.',
                'icon' => 'heroicon-o-information-circle',
            ],
        ];
    }
}
