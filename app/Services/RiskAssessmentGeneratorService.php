<?php

namespace App\Services;

use App\Models\User;
use App\Models\Expense;
use App\Models\RevenueSource;
use App\Models\RiskAssessment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RiskAssessmentGeneratorService
{
    protected OpenAIService $openAI;
    protected FinancialMetricsCalculator $calculator;

    public function __construct(OpenAIService $openAI, FinancialMetricsCalculator $calculator)
    {
        $this->openAI = $openAI;
        $this->calculator = $calculator;
    }

    /**
     * Generate risk assessment using AI and business rules
     */
    public function generateRisks(int $userId, int $days = 90): array
    {
        // Gather business data
        $businessData = $this->gatherBusinessData($userId, $days);

        if (empty($businessData['has_data'])) {
            return [
                'success' => false,
                'message' => 'Not enough business data to assess risks. Please add expenses and revenue data first.',
                'created' => 0,
            ];
        }

        // Generate risks using rules
        $risks = $this->generateWithRules($businessData);

        // Enhance with AI if available
        if (!empty(config('services.openai.key'))) {
            try {
                $aiRisks = $this->generateWithAI($businessData);
                if ($aiRisks) {
                    $risks = array_merge($risks, $aiRisks);
                }
            } catch (\Exception $e) {
                Log::warning('AI risk generation failed, using rule-based only', ['error' => $e->getMessage()]);
            }
        }

        return $this->createRiskRecords($userId, $risks);
    }

    /**
     * Gather business data for risk analysis
     */
    protected function gatherBusinessData(int $userId, int $days): array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['has_data' => false];
        }

        $startDate = Carbon::today()->subDays($days);

        $expenses = Expense::where('user_id', $userId)
            ->where('date', '>=', $startDate)
            ->get();

        $revenue = RevenueSource::where('user_id', $userId)
            ->where('date', '>=', $startDate)
            ->get();

        // Get current and previous metrics using calculator
        $currentMetrics = $this->calculator->getCurrentMonthMetrics($user);
        $previousMetrics = $this->calculator->getLastMonthMetrics($user);

        // Calculate cash flow change
        $cashFlowChange = $this->calculator->calculatePercentageChange(
            $currentMetrics['cash_flow'],
            $previousMetrics['cash_flow']
        );

        // Get revenue trend for volatility calculation
        $revenueTrend = $this->calculator->getMonthlyTrend($user, 'revenue');
        $expenseTrend = $this->calculator->getMonthlyTrend($user, 'expenses');

        // Calculate volatility
        $revenueVolatility = count($revenueTrend) > 0 ? $this->calculateVolatility($revenueTrend) : 0;
        $expenseVolatility = count($expenseTrend) > 0 ? $this->calculateVolatility($expenseTrend) : 0;

        // Revenue concentration
        $revenueSources = $revenue->groupBy('source')->map(fn($items) => $items->sum('amount'));
        $totalRevenue = $revenue->sum('amount');
        $revenueConcentration = $revenueSources->isNotEmpty() ? ($revenueSources->max() / max($totalRevenue, 1)) * 100 : 0;

        return [
            'has_data' => $expenses->isNotEmpty() || $revenue->isNotEmpty() || $currentMetrics['revenue'] > 0,
            'period_days' => $days,
            'total_revenue' => $currentMetrics['revenue'],
            'total_expenses' => $currentMetrics['expenses'],
            'latest_cash_flow' => $currentMetrics['cash_flow'],
            'cash_flow_change' => $cashFlowChange,
            'revenue_volatility' => $revenueVolatility,
            'expense_volatility' => $expenseVolatility,
            'revenue_concentration' => $revenueConcentration,
            'profit_margin' => $currentMetrics['profit_margin'],
            'expense_categories' => $expenses->groupBy('category')->count(),
            'revenue_sources_count' => $revenueSources->count(),
        ];
    }

    /**
     * Calculate coefficient of variation (volatility)
     */
    protected function calculateVolatility(array $values): float
    {
        if (count($values) < 2) return 0;

        $mean = array_sum($values) / count($values);
        if ($mean == 0) return 0;

        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        $stdDev = sqrt($variance);

        return ($stdDev / $mean) * 100; // Coefficient of variation
    }

    /**
     * Generate risks with AI
     */
    protected function generateWithAI(array $data): ?array
    {
        $prompt = "Analyze this business financial data and identify specific risks:\n\n";
        $prompt .= "- Revenue Volatility: " . number_format($data['revenue_volatility'], 1) . "%\n";
        $prompt .= "- Expense Volatility: " . number_format($data['expense_volatility'], 1) . "%\n";
        $prompt .= "- Cash Flow Change: " . number_format($data['cash_flow_change'], 1) . "%\n";
        $prompt .= "- Revenue Concentration: " . number_format($data['revenue_concentration'], 1) . "% in top source\n";
        $prompt .= "- Profit Margin: " . number_format($data['profit_margin'], 1) . "%\n";
        $prompt .= "- Number of Revenue Sources: " . $data['revenue_sources_count'] . "\n\n";
        $prompt .= "Identify 2-3 specific business risks with severity (low/medium/high) and mitigation strategies. Return ONLY valid JSON: [{\"risk_type\": \"...\", \"description\": \"...\", \"severity\": \"...\", \"likelihood\": \"...\", \"mitigation\": \"...\"}]";

        $response = $this->openAI->chat([
            ['role' => 'system', 'content' => 'You are a business risk analyst. Identify specific, actionable risks based on financial data. Return ONLY valid JSON array.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.5, 'max_tokens' => 1000]);

        if ($response) {
            try {
                if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
                    return json_decode($matches[0], true);
                }
            } catch (\Exception $e) {
                Log::error('Failed to parse AI risk response', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Generate risks with business rules
     */
    protected function generateWithRules(array $data): array
    {
        $risks = [];

        // Cash flow risk
        if ($data['latest_cash_flow'] < 0) {
            $risks[] = [
                'risk_type' => 'Financial',
                'description' => "Negative cash flow of $" . number_format(abs($data['latest_cash_flow']), 2) . " threatens immediate business operations and solvency.",
                'severity' => 'high',
                'likelihood' => 'high',
                'mitigation' => "Immediate actions: Reduce discretionary expenses, accelerate receivables collection, delay non-critical payments, and secure short-term financing if needed.",
            ];
        } elseif ($data['cash_flow_change'] < -20) {
            $risks[] = [
                'risk_type' => 'Financial',
                'description' => "Cash flow declining by " . number_format(abs($data['cash_flow_change']), 1) . "% indicates deteriorating financial health.",
                'severity' => 'medium',
                'likelihood' => 'high',
                'mitigation' => "Monitor cash flow weekly, identify causes of decline, implement cost control measures, and improve payment collection processes.",
            ];
        }

        // Revenue volatility risk
        if ($data['revenue_volatility'] > 30) {
            $risks[] = [
                'risk_type' => 'Revenue',
                'description' => "High revenue volatility (" . number_format($data['revenue_volatility'], 1) . "%) creates unpredictable cash flow and planning challenges.",
                'severity' => 'medium',
                'likelihood' => 'medium',
                'mitigation' => "Diversify revenue streams, establish recurring revenue models, build financial reserves to buffer volatility, and improve forecasting.",
            ];
        }

        // Revenue concentration risk
        if ($data['revenue_concentration'] > 50) {
            $risks[] = [
                'risk_type' => 'Revenue',
                'description' => number_format($data['revenue_concentration'], 0) . "% of revenue from single source creates dangerous dependency.",
                'severity' => $data['revenue_concentration'] > 70 ? 'high' : 'medium',
                'likelihood' => 'medium',
                'mitigation' => "Actively develop alternative revenue streams, expand customer base, invest in new products/services, and negotiate favorable terms with top client.",
            ];
        }

        // Low profit margin risk
        if ($data['profit_margin'] < 10) {
            $risks[] = [
                'risk_type' => 'Financial',
                'description' => "Low profit margin (" . number_format($data['profit_margin'], 1) . "%) leaves little buffer for unexpected costs or economic downturns.",
                'severity' => $data['profit_margin'] < 5 ? 'high' : 'medium',
                'likelihood' => 'high',
                'mitigation' => "Review pricing strategy, identify and eliminate inefficiencies, negotiate better supplier terms, automate repetitive tasks, and focus on high-margin products/services.",
            ];
        }

        // Expense volatility risk
        if ($data['expense_volatility'] > 40) {
            $risks[] = [
                'risk_type' => 'Operational',
                'description' => "High expense volatility (" . number_format($data['expense_volatility'], 1) . "%) indicates poor cost control and budgeting.",
                'severity' => 'medium',
                'likelihood' => 'high',
                'mitigation' => "Implement budget controls, negotiate fixed-price contracts, automate expense tracking, and establish approval workflows for discretionary spending.",
            ];
        }

        // Limited revenue sources risk
        if ($data['revenue_sources_count'] <= 1) {
            $risks[] = [
                'risk_type' => 'Strategic',
                'description' => "Single revenue source creates vulnerability to market changes and customer loss.",
                'severity' => 'high',
                'likelihood' => 'medium',
                'mitigation' => "Develop new revenue streams, expand into adjacent markets, create complementary products/services, and build strategic partnerships.",
            ];
        }

        // Default risk if no specific risks identified
        if (empty($risks)) {
            $risks[] = [
                'risk_type' => 'Market',
                'description' => "Market competition and economic uncertainty require continuous monitoring and adaptation.",
                'severity' => 'low',
                'likelihood' => 'medium',
                'mitigation' => "Stay informed about industry trends, maintain competitive advantages, build strong customer relationships, and keep financial reserves.",
            ];
        }

        return $risks;
    }

    /**
     * Create risk records in database
     */
    protected function createRiskRecords(int $userId, array $risks): array
    {
        // Calculate overall risk score
        $riskScore = $this->calculateRiskScore($risks);
        $riskLevel = match(true) {
            $riskScore < 25 => 'low',
            $riskScore < 50 => 'moderate',
            $riskScore < 75 => 'high',
            default => 'critical',
        };

        // Calculate loan worthiness (inverse of risk)
        $loanWorthiness = 100 - $riskScore;
        $loanWorthinessLevel = match(true) {
            $loanWorthiness >= 80 => 'excellent',
            $loanWorthiness >= 60 => 'good',
            $loanWorthiness >= 40 => 'fair',
            default => 'poor',
        };

        // Format risk factors
        $riskFactors = array_map(function($risk) {
            return [
                'type' => $risk['risk_type'] ?? 'General',
                'description' => $risk['description'],
                'severity' => $risk['severity'] ?? 'medium',
                'likelihood' => $risk['likelihood'] ?? 'medium',
                'mitigation' => $risk['mitigation'] ?? 'Monitor regularly',
            ];
        }, $risks);

        $record = RiskAssessment::create([
            'user_id' => $userId,
            'date' => Carbon::today(),
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'loan_worthiness' => $loanWorthiness,
            'loan_worthiness_level' => $loanWorthinessLevel,
            'risk_factors' => $riskFactors,
        ]);

        return [
            'success' => true,
            'created' => 1,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors_count' => count($risks),
            'item' => $record,
        ];
    }

    /**
     * Calculate overall risk score from individual risks
     */
    protected function calculateRiskScore(array $risks): int
    {
        if (empty($risks)) return 25; // Default low-moderate risk

        $severityScores = ['low' => 20, 'medium' => 50, 'high' => 80];
        $likelihoodScores = ['low' => 20, 'medium' => 50, 'high' => 80];

        $totalScore = 0;
        $count = 0;

        foreach ($risks as $risk) {
            $severity = $severityScores[$risk['severity'] ?? 'medium'] ?? 50;
            $likelihood = $likelihoodScores[$risk['likelihood'] ?? 'medium'] ?? 50;

            // Risk score = (Severity + Likelihood) / 2
            $totalScore += ($severity + $likelihood) / 2;
            $count++;
        }

        return min(100, (int) ($totalScore / max($count, 1)));
    }
}
