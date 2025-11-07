<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\MarketingMetric;
use App\Services\OpenAIService;
use App\Services\AnomalyDetectionService;
use App\Services\FinancialMetricsCalculator;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AIBusinessInsightsWidget extends Widget
{
    protected static ?string $heading = '🤖 AI Business Insights';

    protected static ?int $sort = 14;

    protected string $view = 'filament.dashboard.widgets.ai-business-insights-widget';

    protected int | string | array $columnSpan = 'full';

    public ?array $insights = null;

    public ?array $anomalies = null;

    public ?array $forecasts = null;

    public bool $isLoading = false;

    public function mount()
    {
        $this->loadInsights();
    }

    public function loadInsights()
    {
        $this->isLoading = true;

        try {
            $user = auth()->user();
            $cacheKey = "ai_insights_{$user->id}_" . now()->format('Y-m-d-H');

            // Cache for 2 hours to reduce API costs
            $cachedData = Cache::remember($cacheKey, 7200, function () use ($user) {
                return $this->generateAllInsights($user);
            });

            $this->insights = $cachedData['insights'];
            $this->anomalies = $cachedData['anomalies'];
            $this->forecasts = $cachedData['forecasts'];
        } catch (\Exception $e) {
            $this->insights = [[
                'type' => 'warning',
                'title' => 'AI Insights Unavailable',
                'description' => 'Unable to generate insights. Please check your OpenAI API configuration.',
                'icon' => 'heroicon-o-exclamation-triangle',
            ]];
            $this->anomalies = [];
            $this->forecasts = [];
        } finally {
            $this->isLoading = false;
        }
    }

    protected function generateAllInsights($user): array
    {
        // Gather comprehensive business data
        $businessData = $this->gatherBusinessData($user);

        // Detect anomalies
        $anomalies = $this->detectAnomalies($user);

        // Generate AI insights
        $insights = $this->generateAIInsights($user, $businessData, $anomalies);

        // Generate forecasts
        $forecasts = $this->generateForecasts($user, $businessData);

        return [
            'insights' => $insights,
            'anomalies' => $anomalies,
            'forecasts' => $forecasts,
        ];
    }

    protected function gatherBusinessData($user): array
    {
        $calculator = app(FinancialMetricsCalculator::class);
        $startOfMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Get current and previous month metrics
        $currentMetrics = $calculator->getCurrentMonthMetrics($user);
        $previousMetrics = $calculator->getLastMonthMetrics($user);

        // Calculate changes
        $revenueChange = $calculator->calculatePercentageChange(
            $currentMetrics['revenue'],
            $previousMetrics['revenue']
        );

        $expenseChange = $calculator->calculatePercentageChange(
            $currentMetrics['expenses'],
            $previousMetrics['expenses']
        );

        // Get revenue trend
        $revenueTrend = $calculator->getMonthlyTrend($user, 'revenue');

        // Get marketing metrics
        $currentMarketingMetrics = MarketingMetric::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('
                SUM(ad_spend) as total_ad_spend,
                AVG(roi) as avg_roi,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads,
                AVG(customer_acquisition_cost) as avg_cac,
                AVG(clv_cac_ratio) as avg_clv_cac_ratio,
                AVG(cost_per_conversion) as avg_cost_per_conversion
            ')
            ->first();

        $previousMarketingMetrics = MarketingMetric::where('user_id', $user->id)
            ->whereBetween('date', [$lastMonth, $lastMonthEnd])
            ->selectRaw('
                SUM(ad_spend) as total_ad_spend,
                AVG(roi) as avg_roi,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads
            ')
            ->first();

        // Calculate marketing changes
        $adSpendChange = $this->calculatePercentageChange(
            $currentMarketingMetrics->total_ad_spend ?? 0,
            $previousMarketingMetrics->total_ad_spend ?? 0
        );

        $marketingConversionChange = $this->calculatePercentageChange(
            $currentMarketingMetrics->total_conversions ?? 0,
            $previousMarketingMetrics->total_conversions ?? 0
        );

        return [
            'current_revenue' => $currentMetrics['revenue'],
            'previous_revenue' => $previousMetrics['revenue'],
            'revenue_change' => $revenueChange,
            'current_profit' => $currentMetrics['profit'],
            'current_expenses' => $currentMetrics['expenses'],
            'expense_change' => $expenseChange,
            'cash_flow' => $currentMetrics['cash_flow'],
            'profit_margin' => $currentMetrics['profit_margin'],
            'metrics_count' => count(array_filter($revenueTrend)),
            'avg_revenue' => count($revenueTrend) > 0 ? array_sum($revenueTrend) / count($revenueTrend) : 0,
            'avg_expenses' => $currentMetrics['expenses'],
            'revenue_trend' => $revenueTrend,
            'new_customers_this_month' => Contact::where('user_id', $user->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'top_expense_category' => $this->getTopExpenseCategory($user),
            'active_goals' => Goal::where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->count(),
            // Marketing metrics
            'total_ad_spend' => $currentMarketingMetrics->total_ad_spend ?? 0,
            'ad_spend_change' => $adSpendChange,
            'avg_marketing_roi' => $currentMarketingMetrics->avg_roi ?? 0,
            'total_marketing_conversions' => $currentMarketingMetrics->total_conversions ?? 0,
            'marketing_conversion_change' => $marketingConversionChange,
            'total_marketing_leads' => $currentMarketingMetrics->total_leads ?? 0,
            'avg_cac' => $currentMarketingMetrics->avg_cac ?? 0,
            'avg_clv_cac_ratio' => $currentMarketingMetrics->avg_clv_cac_ratio ?? 0,
            'avg_cost_per_conversion' => $currentMarketingMetrics->avg_cost_per_conversion ?? 0,
        ];
    }

    protected function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function getTopExpenseCategory($user): ?string
    {
        $topExpense = Expense::where('user_id', $user->id)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        return $topExpense->category ?? null;
    }

    protected function detectAnomalies($user): array
    {
        try {
            $anomalyService = app(AnomalyDetectionService::class);
            $detectedAnomalies = $anomalyService->detectMetricAnomalies($user->id);

            return collect($detectedAnomalies)->map(function ($anomaly) {
                return [
                    'metric' => $anomaly['metric'] ?? 'Unknown',
                    'severity' => $anomaly['severity'] ?? 'low',
                    'message' => $anomaly['message'] ?? $anomaly['description'] ?? 'Anomaly detected',
                    'value' => $anomaly['value'] ?? null,
                    'expected' => $anomaly['expected'] ?? null,
                    'deviation' => $anomaly['deviation'] ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function generateAIInsights($user, array $data, array $anomalies): array
    {
        try {
            $openAI = app(OpenAIService::class);

            $prompt = $this->buildInsightsPrompt($data, $anomalies);
            $response = $openAI->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a business intelligence advisor. Analyze business metrics and provide actionable insights and recommendations.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], [
                'feature' => 'business_insights',
                'action' => 'generate',
            ]);

            if ($response) {
                return $this->parseAIResponse($response);
            }

            return $this->getFallbackInsights($data, $anomalies);
        } catch (\Exception $e) {
            return $this->getFallbackInsights($data, $anomalies);
        }
    }

    protected function buildInsightsPrompt(array $data, array $anomalies): string
    {
        $anomalyText = '';
        if (!empty($anomalies)) {
            $anomalyText = "\n\nAnomalies detected:\n";
            foreach ($anomalies as $anomaly) {
                $anomalyText .= "- {$anomaly['message']} (Severity: {$anomaly['severity']})\n";
            }
        }

        $marketingText = '';
        if ($data['total_ad_spend'] > 0) {
            $marketingText = "\n\nMarketing Performance:
- Total Ad Spend: \${$data['total_ad_spend']} ({$data['ad_spend_change']}% change)
- Average Marketing ROI: {$data['avg_marketing_roi']}%
- Marketing Conversions: {$data['total_marketing_conversions']} ({$data['marketing_conversion_change']}% change)
- Marketing Leads: {$data['total_marketing_leads']}
- Avg Customer Acquisition Cost (CAC): \${$data['avg_cac']}
- Avg CLV:CAC Ratio: {$data['avg_clv_cac_ratio']}:1
- Avg Cost Per Conversion: \${$data['avg_cost_per_conversion']}";
        }

        return <<<PROMPT
You are a business intelligence advisor. Analyze the following business metrics and provide 3-5 actionable insights and recommendations.

Current Business Performance:
- Revenue: \${$data['current_revenue']} ({$data['revenue_change']}% change)
- Profit: \${$data['current_profit']} (Margin: {$data['profit_margin']}%)
- Expenses: \${$data['current_expenses']} ({$data['expense_change']}% change)
- Cash Flow: \${$data['cash_flow']}
- New Customers This Month: {$data['new_customers_this_month']}
- Top Expense Category: {$data['top_expense_category']}
{$marketingText}
{$anomalyText}

Provide insights in this exact format (no markdown, just plain text with numbers):

1. [Insight Type]: [Insight Title]
   [2-3 sentence description with specific recommendations]

2. [Insight Type]: [Insight Title]
   [2-3 sentence description with specific recommendations]

Focus on:
- Revenue optimization opportunities
- Cost reduction strategies
- Cash flow management
- Marketing ROI and customer acquisition efficiency
- Correlation between marketing spend and revenue growth
- CLV:CAC health and unit economics
- Growth opportunities
- Risk mitigation

Keep each insight actionable and specific to the data provided. When marketing data is available, highlight relationships between marketing performance and business outcomes.
PROMPT;
    }

    protected function parseAIResponse(string $response): array
    {
        $insights = [];
        $lines = explode("\n", trim($response));
        $currentInsight = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if it's a numbered insight
            if (preg_match('/^(\d+)\.\s*\[?([^\]]+)\]?:\s*(.+)$/', $line, $matches)) {
                if ($currentInsight) {
                    $insights[] = $currentInsight;
                }

                $type = strtolower($matches[2]);
                $title = $matches[3];

                $currentInsight = [
                    'type' => $this->mapInsightType($type),
                    'title' => $title,
                    'description' => '',
                    'icon' => $this->getIconForInsightType($this->mapInsightType($type)),
                ];
            } else {
                // Continuation of description
                if ($currentInsight) {
                    $currentInsight['description'] .= ' ' . $line;
                }
            }
        }

        if ($currentInsight) {
            $insights[] = $currentInsight;
        }

        return !empty($insights) ? $insights : $this->parseFallbackFormat($response);
    }

    protected function parseFallbackFormat(string $response): array
    {
        // If structured parsing fails, return as single insight
        return [[
            'type' => 'info',
            'title' => 'AI Business Analysis',
            'description' => $response,
            'icon' => 'heroicon-o-light-bulb',
        ]];
    }

    protected function mapInsightType(string $type): string
    {
        $type = strtolower($type);

        if (str_contains($type, 'warning') || str_contains($type, 'risk') || str_contains($type, 'concern')) {
            return 'warning';
        }

        if (str_contains($type, 'opportunity') || str_contains($type, 'growth') || str_contains($type, 'recommendation')) {
            return 'success';
        }

        if (str_contains($type, 'alert') || str_contains($type, 'critical')) {
            return 'danger';
        }

        return 'info';
    }

    protected function getIconForInsightType(string $type): string
    {
        return match($type) {
            'warning' => 'heroicon-o-exclamation-triangle',
            'danger' => 'heroicon-o-exclamation-circle',
            'success' => 'heroicon-o-light-bulb',
            'info' => 'heroicon-o-information-circle',
            default => 'heroicon-o-chart-bar',
        };
    }

    protected function getFallbackInsights(array $data, array $anomalies): array
    {
        $insights = [];

        // Revenue insight
        if ($data['revenue_change'] < -10) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Revenue Decline Alert',
                'description' => "Revenue dropped {$data['revenue_change']}% this period. Review your sales pipeline and customer retention strategies. Consider launching targeted marketing campaigns to boost acquisition.",
                'icon' => 'heroicon-o-exclamation-circle',
            ];
        } elseif ($data['revenue_change'] > 15) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong Revenue Growth',
                'description' => "Excellent! Revenue increased {$data['revenue_change']}% this period. Capitalize on this momentum by investing in customer success and expansion opportunities.",
                'icon' => 'heroicon-o-arrow-trending-up',
            ];
        }

        // Expense-specific insights
        $expenseInsights = $this->getExpenseInsights($data);
        $insights = array_merge($insights, $expenseInsights);

        // Revenue-specific insights
        $revenueInsights = $this->getRevenueInsights($data);
        $insights = array_merge($insights, $revenueInsights);

        // Marketing-specific insights
        $marketingInsights = $this->getMarketingInsights($data);
        $insights = array_merge($insights, $marketingInsights);

        // Profit margin insight
        if ($data['profit_margin'] < 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Profit Margin',
                'description' => "Your profit margin is {$data['profit_margin']}%, which is below healthy benchmarks. Review pricing strategy and identify cost optimization opportunities, especially in {$data['top_expense_category']}.",
                'icon' => 'heroicon-o-exclamation-triangle',
            ];
        }

        // Cash flow insight
        if ($data['cash_flow'] < 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Negative Cash Flow',
                'description' => "Your cash flow is negative at \$" . number_format(abs($data['cash_flow'])) . ". Prioritize collecting outstanding invoices and consider delaying non-essential expenses.",
                'icon' => 'heroicon-o-exclamation-circle',
            ];
        }

        // Customer growth insight
        if ($data['new_customers_this_month'] == 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'No New Customer Acquisition',
                'description' => "No new customers acquired this month. Increase marketing efforts and review your customer acquisition channels. Consider referral programs or partnerships.",
                'icon' => 'heroicon-o-user-group',
            ];
        }

        // Default insight if none triggered
        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Business Performance Summary',
                'description' => "Your business metrics are stable. Current profit margin is {$data['profit_margin']}% with \$" . number_format($data['current_revenue']) . " in revenue. Continue monitoring trends for optimization opportunities.",
                'icon' => 'heroicon-o-chart-bar',
            ];
        }

        return $insights;
    }

    protected function generateForecasts($user, array $data): array
    {
        if ($data['metrics_count'] < 3) {
            return [];
        }

        try {
            // Simple linear regression for next period forecast
            $revenueTrend = $data['revenue_trend'];
            $trendCount = count($revenueTrend);

            if ($trendCount < 3) {
                return [];
            }

            // Calculate simple moving average
            $recentAvg = array_sum(array_slice($revenueTrend, -3)) / 3;
            $olderAvg = array_sum(array_slice($revenueTrend, 0, 3)) / 3;
            $trend = $recentAvg - $olderAvg;

            $nextMonthRevenue = $data['current_revenue'] + $trend;
            
            // Avoid division by zero when current_revenue is 0
            if ($data['current_revenue'] <= 0) {
                // If no current revenue, use trend-based confidence
                $confidence = abs($trend) > 0 ? 70 : 50;
            } else {
                $confidence = min(95, max(60, 100 - (abs($trend) / $data['current_revenue']) * 100));
            }

            return [
                [
                    'metric' => 'Revenue',
                    'current_value' => $data['current_revenue'],
                    'forecast_value' => max(0, $nextMonthRevenue),
                    'confidence' => round($confidence),
                    'trend' => $trend > 0 ? 'increasing' : 'decreasing',
                    'period' => 'Next Month',
                ],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function refreshInsights()
    {
        // Clear cache and reload
        $user = auth()->user();
        $cacheKey = "ai_insights_{$user->id}_" . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);

        $this->loadInsights();
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

    protected function getExpenseInsights(array $data): array
    {
        $insights = [];
        $user = auth()->user();
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $startOfLastMonth = \Carbon\Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = \Carbon\Carbon::now()->subMonth()->endOfMonth();

        // Current and last month expenses
        $currentExpenses = \App\Models\Expense::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->sum('amount');

        $lastExpenses = \App\Models\Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $expenseChange = $lastExpenses > 0 ? (($currentExpenses - $lastExpenses) / $lastExpenses) * 100 : 0;

        // Check for unusual expense increase
        if ($expenseChange > 30) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Significant Expense Increase',
                'description' => "Expenses increased by " . number_format($expenseChange, 1) . "% this month. Review recent purchases and identify if this is a one-time spike or a trend. Top category: {$data['top_expense_category']}.",
                'icon' => 'heroicon-o-arrow-trending-up',
            ];
        }

        // Check for expense reduction opportunity
        if ($expenseChange < -15) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Great Cost Control',
                'description' => "Expenses decreased by " . number_format(abs($expenseChange), 1) . "% this month. Excellent cost management! Maintain this discipline to improve profit margins.",
                'icon' => 'heroicon-o-arrow-trending-down',
            ];
        }

        // Tax deductible insights
        $taxDeductible = \App\Models\Expense::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->where('is_tax_deductible', true)
            ->sum('amount');

        $deductiblePercentage = $currentExpenses > 0 ? ($taxDeductible / $currentExpenses) * 100 : 0;

        if ($deductiblePercentage < 30 && $currentExpenses > 1000) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Tax Deduction Opportunity',
                'description' => "Only " . number_format($deductiblePercentage, 0) . "% of expenses are marked as tax deductible. Review your expenses to ensure all eligible deductions are captured for tax savings.",
                'icon' => 'heroicon-o-document-text',
            ];
        }

        return $insights;
    }

    protected function getRevenueInsights(array $data): array
    {
        $insights = [];
        $user = auth()->user();
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $startOfLastMonth = \Carbon\Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = \Carbon\Carbon::now()->subMonth()->endOfMonth();

        // Current and last month revenue
        $currentRevenue = \App\Models\RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->sum('amount');

        $lastRevenue = \App\Models\RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        // Revenue diversification check
        $uniqueSources = \App\Models\RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->distinct('source')
            ->count('source');

        $topSource = \App\Models\RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->selectRaw('source, SUM(amount) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->first();

        // Check for revenue concentration risk
        if ($topSource && $currentRevenue > 0) {
            $concentration = ($topSource->total / $currentRevenue) * 100;

            if ($concentration > 70 && $uniqueSources < 3) {
                $sourceName = ucwords(str_replace('_', ' ', $topSource->source));
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Revenue Concentration Risk',
                    'description' => "Over " . number_format($concentration, 0) . "% of revenue comes from {$sourceName}. Diversify revenue streams to reduce business risk and improve stability.",
                    'icon' => 'heroicon-o-exclamation-triangle',
                ];
            } elseif ($uniqueSources >= 4) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Well-Diversified Revenue',
                    'description' => "Excellent! You have {$uniqueSources} active revenue streams. This diversification reduces risk and creates more stability for your business.",
                    'icon' => 'heroicon-o-squares-2x2',
                ];
            }
        }

        // MRR (Monthly Recurring Revenue) insights
        $mrr = \App\Models\RevenueSource::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->where('source', 'subscriptions')
            ->sum('amount');

        $lastMrr = \App\Models\RevenueSource::where('user_id', $user->id)
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->where('source', 'subscriptions')
            ->sum('amount');

        if ($mrr > 0) {
            $mrrPercentage = $currentRevenue > 0 ? ($mrr / $currentRevenue) * 100 : 0;
            $mrrChange = $lastMrr > 0 ? (($mrr - $lastMrr) / $lastMrr) * 100 : 0;

            if ($mrrPercentage > 50) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Strong Recurring Revenue Base',
                    'description' => "Excellent! " . number_format($mrrPercentage, 0) . "% of revenue is recurring (MRR: $" . number_format($mrr, 0) . "). This provides predictable cash flow and business stability.",
                    'icon' => 'heroicon-o-arrow-path',
                ];
            } elseif ($mrrChange > 10) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Growing Recurring Revenue',
                    'description' => "MRR increased " . number_format($mrrChange, 1) . "% this month. Continue focusing on subscription growth to build a more predictable revenue stream.",
                    'icon' => 'heroicon-o-arrow-trending-up',
                ];
            }
        } elseif ($currentRevenue > 5000) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Consider Recurring Revenue',
                'description' => "You have no recurring revenue streams yet. Consider subscription-based offerings or retainer contracts to create predictable monthly income and improve cash flow stability.",
                'icon' => 'heroicon-o-arrow-path',
            ];
        }

        // Revenue volatility check
        if ($lastRevenue > 0) {
            $revenueChange = (($currentRevenue - $lastRevenue) / $lastRevenue) * 100;

            if (abs($revenueChange) > 40) {
                $direction = $revenueChange > 0 ? 'increased' : 'decreased';
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'High Revenue Volatility',
                    'description' => "Revenue {$direction} " . number_format(abs($revenueChange), 0) . "% this month. High volatility can make planning difficult. Focus on building recurring revenue to stabilize income.",
                    'icon' => 'heroicon-o-chart-bar',
                ];
            }
        }

        return $insights;
    }

    protected function getMarketingInsights(array $data): array
    {
        $insights = [];

        // Only generate insights if there's marketing data
        if ($data['total_ad_spend'] == 0) {
            return $insights;
        }

        // 1. Marketing ROI Analysis
        $avgROI = $data['avg_marketing_roi'];
        if ($avgROI < 50) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Critical: Low Marketing ROI',
                'description' => sprintf(
                    'Marketing ROI is only %.1f%%, meaning you\'re losing money on ads. With $%.0f in ad spend this month, review targeting, creative performance, and landing page effectiveness immediately.',
                    $avgROI,
                    $data['total_ad_spend']
                ),
                'icon' => 'heroicon-o-exclamation-circle',
            ];
        } elseif ($avgROI >= 200) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Outstanding Marketing ROI',
                'description' => sprintf(
                    'Excellent! Marketing ROI of %.1f%% means you\'re generating $%.2f for every $1 spent. Consider increasing ad budget to scale these profitable campaigns.',
                    $avgROI,
                    ($avgROI / 100) + 1
                ),
                'icon' => 'heroicon-o-trophy',
            ];
        } elseif ($avgROI >= 100 && $avgROI < 200) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Healthy Marketing ROI',
                'description' => sprintf(
                    'Marketing ROI of %.1f%% is profitable. Test scaling top-performing channels and optimize underperforming ones to push ROI above 200%%.',
                    $avgROI
                ),
                'icon' => 'heroicon-o-chart-bar',
            ];
        }

        // 2. CLV:CAC Ratio Analysis
        $clvCacRatio = $data['avg_clv_cac_ratio'];
        if ($clvCacRatio > 0) {
            if ($clvCacRatio < 1) {
                $insights[] = [
                    'type' => 'danger',
                    'title' => 'Critical: CAC Exceeds CLV',
                    'description' => sprintf(
                        'Your CLV:CAC ratio is %.2f:1. You\'re spending $%.2f to acquire customers worth $%.2f. Pause underperforming channels immediately or improve customer lifetime value.',
                        $clvCacRatio,
                        $data['avg_cac'],
                        $data['avg_cac'] * $clvCacRatio
                    ),
                    'icon' => 'heroicon-o-exclamation-circle',
                ];
            } elseif ($clvCacRatio >= 3) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Excellent Unit Economics',
                    'description' => sprintf(
                        'CLV:CAC ratio of %.2f:1 indicates healthy unit economics. Your marketing channels are highly profitable — consider increasing budget allocation.',
                        $clvCacRatio
                    ),
                    'icon' => 'heroicon-o-check-badge',
                ];
            } elseif ($clvCacRatio >= 1 && $clvCacRatio < 2) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'CLV:CAC Ratio Needs Improvement',
                    'description' => sprintf(
                        'CLV:CAC ratio of %.2f:1 is barely profitable. Aim for 3:1 by either reducing CAC (optimize ads) or increasing CLV (upsells, retention).',
                        $clvCacRatio
                    ),
                    'icon' => 'heroicon-o-exclamation-triangle',
                ];
            }
        }

        // 3. Marketing vs Revenue Correlation
        if ($data['current_revenue'] > 0 && $data['total_marketing_conversions'] > 0) {
            $revenueChange = $data['revenue_change'];
            $conversionChange = $data['marketing_conversion_change'];

            // Check if marketing is driving revenue
            if ($conversionChange > 20 && $revenueChange < 5) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Sales Funnel Bottleneck Detected',
                    'description' => sprintf(
                        'Marketing conversions increased %.0f%% but revenue only grew %.0f%%. Investigate sales process, pricing, or lead quality issues.',
                        $conversionChange,
                        $revenueChange
                    ),
                    'icon' => 'heroicon-o-funnel',
                ];
            } elseif ($revenueChange > 15 && $conversionChange > 10) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Marketing Driving Revenue Growth',
                    'description' => sprintf(
                        'Strong correlation: marketing conversions up %.0f%% and revenue up %.0f%%. Your marketing and sales are aligned — continue this momentum.',
                        $conversionChange,
                        $revenueChange
                    ),
                    'icon' => 'heroicon-o-arrow-trending-up',
                ];
            }
        }

        // 4. Ad Spend Efficiency
        $adSpendChange = $data['ad_spend_change'];
        if (abs($adSpendChange) > 30) {
            if ($adSpendChange > 30 && $avgROI < 100) {
                $insights[] = [
                    'type' => 'danger',
                    'title' => 'Inefficient Ad Spend Increase',
                    'description' => sprintf(
                        'Ad spend increased %.0f%% to $%.0f but ROI is only %.0f%%. You\'re scaling unprofitable campaigns — pause and optimize before increasing budget.',
                        $adSpendChange,
                        $data['total_ad_spend'],
                        $avgROI
                    ),
                    'icon' => 'heroicon-o-exclamation-circle',
                ];
            } elseif ($adSpendChange > 30 && $avgROI > 150) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'Smart Ad Spend Scaling',
                    'description' => sprintf(
                        'Ad spend increased %.0f%% with strong ROI of %.0f%%. You\'re effectively scaling profitable campaigns. Monitor CAC to ensure it stays healthy.',
                        $adSpendChange,
                        $avgROI
                    ),
                    'icon' => 'heroicon-o-rocket-launch',
                ];
            }
        }

        // 5. Lead Generation Efficiency
        $totalLeads = $data['total_marketing_leads'];
        $totalConversions = $data['total_marketing_conversions'];
        $costPerConversion = $data['avg_cost_per_conversion'];

        if ($totalLeads > 0 && $totalConversions > 0) {
            $leadConversionRate = ($totalConversions / $totalLeads) * 100;

            if ($leadConversionRate < 2 && $costPerConversion > 50) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'High CAC Due to Poor Lead Quality',
                    'description' => sprintf(
                        'Only %.1f%% of leads convert (cost per conversion: $%.2f). Improve ad targeting to attract higher-quality leads or optimize sales process.',
                        $leadConversionRate,
                        $costPerConversion
                    ),
                    'icon' => 'heroicon-o-users',
                ];
            } elseif ($leadConversionRate > 10) {
                $insights[] = [
                    'type' => 'success',
                    'title' => 'High-Quality Lead Generation',
                    'description' => sprintf(
                        'Excellent! %.1f%% of leads are converting. Your targeting and messaging are resonating well with your audience.',
                        $leadConversionRate
                    ),
                    'icon' => 'heroicon-o-user-group',
                ];
            }
        }

        // 6. Customer Acquisition Cost Alert
        if ($data['avg_cac'] > 0 && $data['new_customers_this_month'] > 0) {
            $avgRevPerCustomer = $data['current_revenue'] / max($data['new_customers_this_month'], 1);

            if ($data['avg_cac'] > $avgRevPerCustomer) {
                $insights[] = [
                    'type' => 'danger',
                    'title' => 'CAC Exceeds First Purchase Value',
                    'description' => sprintf(
                        'You\'re spending $%.2f to acquire customers generating $%.2f on average. You\'ll need strong retention and upsells to be profitable.',
                        $data['avg_cac'],
                        $avgRevPerCustomer
                    ),
                    'icon' => 'heroicon-o-currency-dollar',
                ];
            }
        }

        return $insights;
    }
}
