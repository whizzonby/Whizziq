<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\SwotAnalysis;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SwotGeneratorService
{
    protected OpenAIService $openAI;
    protected FinancialMetricsCalculator $calculator;

    public function __construct(OpenAIService $openAI, FinancialMetricsCalculator $calculator)
    {
        $this->openAI = $openAI;
        $this->calculator = $calculator;
    }

    /**
     * Generate SWOT analysis using AI (wrapper for widget)
     */
    public function generateSwotAnalysis(int $userId, int $days = 90): array
    {
        return $this->generateSwot($userId, $days);
    }

    /**
     * Generate SWOT analysis using AI
     */
    public function generateSwot(int $userId, int $days = 90): array
    {
        // Gather business data
        $businessData = $this->gatherBusinessData($userId, $days);

        // Check if we have enough data
        if (empty($businessData['has_data'])) {
            return $this->getDefaultSwot();
        }

        // Try AI generation first
        if (!empty(config('services.openai.key'))) {
            try {
                $aiSwot = $this->generateWithAI($businessData);
                if ($aiSwot) {
                    return $this->createSwotRecords($userId, $aiSwot);
                }
            } catch (\Exception $e) {
                Log::warning('AI SWOT generation failed, using rule-based', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to rule-based generation
        $ruleBasedSwot = $this->generateWithRules($businessData);
        return $this->createSwotRecords($userId, $ruleBasedSwot);
    }

    /**
     * Gather business data for analysis
     */
    protected function gatherBusinessData(int $userId, int $days): array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['has_data' => false];
        }

        $startDate = Carbon::today()->subDays($days);
        $endDate = Carbon::today();

        // Get expenses
        $expenses = Expense::where('user_id', $userId)
            ->where('date', '>=', $startDate)
            ->get();

        // Get revenue
        $revenue = RevenueSource::where('user_id', $userId)
            ->where('date', '>=', $startDate)
            ->get();

        // Get current and previous month metrics using calculator
        $currentMetrics = $this->calculator->getCurrentMonthMetrics($user);
        $previousMetrics = $this->calculator->getLastMonthMetrics($user);

        // Calculate revenue growth
        $revenueGrowth = $this->calculator->calculatePercentageChange(
            $currentMetrics['revenue'],
            $previousMetrics['revenue']
        );

        // Get revenue trend
        $revenueTrend = $this->calculator->getMonthlyTrend($user, 'revenue');

        // Top expense categories
        $topExpenses = $expenses->groupBy('category')
            ->map(fn ($items) => $items->sum('amount'))
            ->sortDesc()
            ->take(3);

        // Revenue sources
        $revenueSources = $revenue->groupBy('source')
            ->map(fn ($items) => $items->sum('amount'))
            ->sortDesc();

        $hasData = $expenses->isNotEmpty() || $revenue->isNotEmpty() || array_sum($revenueTrend) > 0;

        return [
            'has_data' => $hasData,
            'period_days' => $days,
            'total_revenue' => $currentMetrics['revenue'],
            'total_expenses' => $currentMetrics['expenses'],
            'avg_revenue' => count($revenueTrend) > 0 ? array_sum($revenueTrend) / count($revenueTrend) : 0,
            'avg_profit' => $currentMetrics['profit'],
            'latest_cash_flow' => $currentMetrics['cash_flow'],
            'revenue_growth' => $revenueGrowth,
            'profit_margin' => $currentMetrics['profit_margin'],
            'top_expenses' => $topExpenses->toArray(),
            'revenue_sources' => $revenueSources->toArray(),
            'metrics_count' => count(array_filter($revenueTrend)),
        ];
    }

    /**
     * Generate SWOT with AI
     */
    protected function generateWithAI(array $data): ?array
    {
        $prompt = $this->buildPrompt($data);

        $response = $this->openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a business strategy consultant. Analyze the provided business data and generate a comprehensive SWOT analysis (Strengths, Weaknesses, Opportunities, Threats). Each category should have 3-5 specific, actionable items with priority levels (1-10). Respond ONLY with valid JSON in this exact format: {"strengths": [{"description": "...", "priority": 8}], "weaknesses": [...], "opportunities": [...], "threats": [...]}'
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'feature' => 'swot_analysis',
            'action' => 'generate',
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ]);

        if ($response) {
            try {
                // Try to extract JSON from response
                if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                    $json = $matches[0];
                    $decoded = json_decode($json, true);

                    if ($decoded && isset($decoded['strengths'], $decoded['weaknesses'], $decoded['opportunities'], $decoded['threats'])) {
                        return $decoded;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to parse AI SWOT response', ['response' => $response, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Build prompt for AI
     */
    protected function buildPrompt(array $data): string
    {
        $prompt = "Analyze this business data from the last {$data['period_days']} days:\n\n";
        $prompt .= "**Financial Overview:**\n";
        $prompt .= "- Total Revenue: $" . number_format($data['total_revenue'], 2) . "\n";
        $prompt .= "- Total Expenses: $" . number_format($data['total_expenses'], 2) . "\n";
        $prompt .= "- Profit Margin: " . number_format($data['profit_margin'], 1) . "%\n";
        $prompt .= "- Revenue Growth: " . number_format($data['revenue_growth'], 1) . "%\n";
        $prompt .= "- Current Cash Flow: $" . number_format($data['latest_cash_flow'], 2) . "\n\n";

        if (!empty($data['top_expenses'])) {
            $prompt .= "**Top Expense Categories:**\n";
            foreach ($data['top_expenses'] as $category => $amount) {
                $prompt .= "- " . ucwords(str_replace('_', ' ', $category)) . ": $" . number_format($amount, 2) . "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($data['revenue_sources'])) {
            $prompt .= "**Revenue Sources:**\n";
            foreach ($data['revenue_sources'] as $source => $amount) {
                $prompt .= "- " . ucwords(str_replace('_', ' ', $source)) . ": $" . number_format($amount, 2) . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Generate a SWOT analysis with 3-5 items per category. Focus on actionable insights.";

        return $prompt;
    }

    /**
     * Generate SWOT with business rules (fallback)
     */
    protected function generateWithRules(array $data): array
    {
        $swot = [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Strengths
        if ($data['revenue_growth'] > 10) {
            $swot['strengths'][] = [
                'description' => "Strong revenue growth of " . number_format($data['revenue_growth'], 1) . "% indicates market demand and effective sales strategy.",
                'priority' => 9,
            ];
        }

        if ($data['profit_margin'] > 20) {
            $swot['strengths'][] = [
                'description' => "Healthy profit margin of " . number_format($data['profit_margin'], 1) . "% shows efficient cost management and strong pricing power.",
                'priority' => 8,
            ];
        }

        if ($data['latest_cash_flow'] > 0) {
            $swot['strengths'][] = [
                'description' => "Positive cash flow of $" . number_format($data['latest_cash_flow'], 2) . " provides financial stability and growth opportunities.",
                'priority' => 8,
            ];
        }

        if (count($data['revenue_sources']) > 2) {
            $swot['strengths'][] = [
                'description' => "Diversified revenue streams across " . count($data['revenue_sources']) . " sources reduce dependency risk.",
                'priority' => 7,
            ];
        }

        // Weaknesses
        if ($data['revenue_growth'] < 0) {
            $swot['weaknesses'][] = [
                'description' => "Revenue declining by " . number_format(abs($data['revenue_growth']), 1) . "% requires immediate attention to sales and marketing strategies.",
                'priority' => 9,
            ];
        }

        if ($data['profit_margin'] < 10) {
            $swot['weaknesses'][] = [
                'description' => "Low profit margin of " . number_format($data['profit_margin'], 1) . "% suggests high costs or pricing issues.",
                'priority' => 8,
            ];
        }

        if ($data['latest_cash_flow'] < 0) {
            $swot['weaknesses'][] = [
                'description' => "Negative cash flow of $" . number_format(abs($data['latest_cash_flow']), 2) . " threatens business sustainability.",
                'priority' => 10,
            ];
        }

        // Opportunities
        if ($data['revenue_growth'] > 0 && $data['revenue_growth'] < 20) {
            $swot['opportunities'][] = [
                'description' => "Moderate growth of " . number_format($data['revenue_growth'], 1) . "% can be accelerated with increased marketing investment.",
                'priority' => 7,
            ];
        }

        if (!empty($data['top_expenses'])) {
            $topExpense = array_keys($data['top_expenses'])[0];
            $swot['opportunities'][] = [
                'description' => "Optimizing " . ucwords(str_replace('_', ' ', $topExpense)) . " expenses could significantly improve profitability.",
                'priority' => 7,
            ];
        }

        $swot['opportunities'][] = [
            'description' => "Leveraging data analytics and automation can improve operational efficiency and reduce costs.",
            'priority' => 6,
        ];

        // Threats
        if (count($data['revenue_sources']) <= 1) {
            $swot['threats'][] = [
                'description' => "Heavy reliance on single revenue source creates vulnerability to market changes.",
                'priority' => 8,
            ];
        }

        if ($data['profit_margin'] < 15 && $data['revenue_growth'] < 5) {
            $swot['threats'][] = [
                'description' => "Combination of low margins and slow growth makes business vulnerable to economic downturns.",
                'priority' => 8,
            ];
        }

        $swot['threats'][] = [
            'description' => "Market competition and changing customer preferences require continuous innovation and adaptation.",
            'priority' => 6,
        ];

        // Add defaults if categories are empty
        if (empty($swot['strengths'])) {
            $swot['strengths'][] = [
                'description' => "Business is operational and generating revenue, providing foundation for growth.",
                'priority' => 5,
            ];
        }

        if (empty($swot['weaknesses'])) {
            $swot['weaknesses'][] = [
                'description' => "Limited historical data makes it difficult to identify long-term trends and patterns.",
                'priority' => 5,
            ];
        }

        if (empty($swot['opportunities'])) {
            $swot['opportunities'][] = [
                'description' => "Digital transformation and online presence can expand market reach.",
                'priority' => 6,
            ];
        }

        if (empty($swot['threats'])) {
            $swot['threats'][] = [
                'description' => "Economic uncertainty and market volatility require financial prudence and risk management.",
                'priority' => 6,
            ];
        }

        return $swot;
    }

    /**
     * Create SWOT records in database
     */
    protected function createSwotRecords(int $userId, array $swot): array
    {
        $created = [];

        foreach (['strengths' => 'strength', 'weaknesses' => 'weakness', 'opportunities' => 'opportunity', 'threats' => 'threat'] as $category => $type) {
            foreach ($swot[$category] as $item) {
                $record = SwotAnalysis::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'description' => $item['description'],
                    'priority' => $item['priority'] ?? 5,
                ]);

                $created[] = $record;
            }
        }

        return [
            'success' => true,
            'created' => count($created),
            'items' => $created,
        ];
    }

    /**
     * Get default SWOT when no data available
     */
    protected function getDefaultSwot(): array
    {
        return [
            'success' => false,
            'message' => 'Not enough business data to generate SWOT analysis. Please add expenses and revenue data first.',
            'created' => 0,
        ];
    }
}
