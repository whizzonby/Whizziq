<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class TaxOptimizationService
{
    public function __construct(
        protected TaxCalculationService $taxCalculationService,
        protected OpenAIService $openAIService
    ) {}

    /**
     * Generate AI-powered tax optimization recommendations
     */
    public function generateOptimizationRecommendations(User $user): array
    {
        // Gather user's tax data
        $taxData = $this->gatherTaxData($user);

        // Get AI-powered recommendations
        $aiRecommendations = $this->getAIRecommendations($taxData);

        // Get rule-based recommendations
        $ruleBasedRecommendations = $this->getRuleBasedRecommendations($taxData);

        // Combine and prioritize
        return [
            'ai_recommendations' => $aiRecommendations,
            'quick_wins' => $ruleBasedRecommendations,
            'estimated_savings' => $this->estimatePotentialSavings($taxData),
            'optimization_score' => $this->calculateOptimizationScore($taxData),
        ];
    }

    protected function gatherTaxData(User $user): array
    {
        $yearStart = Carbon::now()->startOfYear();
        $summary = $this->taxCalculationService->getYearToDateSummary($user);
        $deductionsByCategory = $this->taxCalculationService->getDeductionsByCategory($user, $yearStart, now());

        $taxSetting = $user->taxSetting;

        return [
            'business_type' => $taxSetting->business_type ?? 'sole_proprietor',
            'total_revenue' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'total_deductions' => $summary['total_deductions'],
            'taxable_income' => $summary['taxable_income'],
            'estimated_tax' => $summary['estimated_tax'],
            'effective_tax_rate' => $summary['effective_tax_rate'],
            'deduction_categories' => $deductionsByCategory,
            'tax_rate' => $taxSetting->tax_rate ?? 25,
            'fiscal_year_end' => $taxSetting->fiscal_year_end ? $taxSetting->fiscal_year_end->format('Y-m-d') : null,
        ];
    }

    protected function getAIRecommendations(array $taxData): array
    {
        try {
            $prompt = $this->buildOptimizationPrompt($taxData);

            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a tax optimization expert. Provide specific, actionable tax strategies.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], [
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            // If OpenAI is not available or returns null, return empty array
            if (!$response) {
                return [];
            }

            // Parse AI response into structured recommendations
            return $this->parseAIResponse($response);
        } catch (\Exception $e) {
            \Log::error('Failed to get AI tax recommendations: ' . $e->getMessage());
            return [];
        }
    }

    protected function buildOptimizationPrompt(array $taxData): string
    {
        $deductionSummary = '';
        foreach ($taxData['deduction_categories'] as $category) {
            $deductionSummary .= "- {$category['category_name']}: \${$category['deductible_amount']}\n";
        }

        return "You are a tax optimization expert. Analyze this business's tax situation and provide 3-5 specific, actionable tax optimization strategies.

Business Profile:
- Business Type: {$taxData['business_type']}
- Annual Revenue: \${$taxData['total_revenue']}
- Total Expenses: \${$taxData['total_expenses']}
- Current Deductions: \${$taxData['total_deductions']}
- Taxable Income: \${$taxData['taxable_income']}
- Effective Tax Rate: {$taxData['effective_tax_rate']}%

Current Deductions by Category:
{$deductionSummary}

Provide recommendations in this format:
1. [Category]: [Specific recommendation]
2. [Category]: [Specific recommendation]
...

Focus on:
- Maximizing legitimate deductions
- Timing strategies for income and expenses
- Business structure optimization
- Retirement and benefit strategies
- Industry-specific deductions

Be specific and actionable. Each recommendation should be 1-2 sentences.";
    }

    protected function parseAIResponse(string $response): array
    {
        $recommendations = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Match numbered list items like "1. [Category]: Description"
            if (preg_match('/^\d+\.\s*\[?([^\]:]+)\]?:?\s*(.+)$/', $line, $matches)) {
                $recommendations[] = [
                    'title' => trim($matches[1]),
                    'description' => trim($matches[2]),
                    'category' => $this->categorizeRecommendation(trim($matches[1])),
                    'priority' => 'medium',
                ];
            }
        }

        // If parsing failed, return raw response as single recommendation
        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Tax Optimization Recommendations',
                'description' => $response,
                'category' => 'general',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    protected function categorizeRecommendation(string $title): string
    {
        $title = strtolower($title);

        if (str_contains($title, 'deduction') || str_contains($title, 'expense')) {
            return 'deductions';
        } elseif (str_contains($title, 'retirement') || str_contains($title, 'pension')) {
            return 'retirement';
        } elseif (str_contains($title, 'structure') || str_contains($title, 'entity')) {
            return 'business_structure';
        } elseif (str_contains($title, 'timing') || str_contains($title, 'defer')) {
            return 'timing';
        }

        return 'general';
    }

    protected function getRuleBasedRecommendations(array $taxData): array
    {
        $recommendations = [];

        // Check for underutilized deductions
        if ($taxData['total_deductions'] < ($taxData['total_revenue'] * 0.15)) {
            $recommendations[] = [
                'title' => 'Low Deduction Rate',
                'description' => 'Your deductions are only ' . round(($taxData['total_deductions'] / $taxData['total_revenue']) * 100, 1) . '% of revenue. Review potential deductions you may be missing.',
                'category' => 'deductions',
                'priority' => 'high',
                'potential_savings' => ($taxData['total_revenue'] * 0.20 - $taxData['total_deductions']) * ($taxData['tax_rate'] / 100),
            ];
        }

        // Check for retirement contributions (for sole proprietors)
        if ($taxData['business_type'] === 'sole_proprietor' && $taxData['total_revenue'] > 50000) {
            $recommendations[] = [
                'title' => 'Retirement Contribution Opportunity',
                'description' => 'As a sole proprietor, consider SEP-IRA or Solo 401(k) contributions to reduce taxable income (up to $66,000/year).',
                'category' => 'retirement',
                'priority' => 'high',
                'potential_savings' => min(66000, $taxData['taxable_income'] * 0.20) * ($taxData['tax_rate'] / 100),
            ];
        }

        // Check for business structure optimization
        if ($taxData['business_type'] === 'sole_proprietor' && $taxData['total_revenue'] > 100000) {
            $recommendations[] = [
                'title' => 'Consider S-Corp Election',
                'description' => 'With revenue over $100k, S-Corp status could reduce self-employment taxes. Consult a tax professional.',
                'category' => 'business_structure',
                'priority' => 'medium',
                'potential_savings' => $taxData['taxable_income'] * 0.03, // Estimated 3% savings
            ];
        }

        // Check for home office deduction
        $hasOfficeDeduction = collect($taxData['deduction_categories'])
            ->where('category_name', 'Rent & Utilities')
            ->isNotEmpty();

        if (!$hasOfficeDeduction && $taxData['total_revenue'] > 0) {
            $recommendations[] = [
                'title' => 'Home Office Deduction',
                'description' => 'If you work from home, claim home office deduction (simplified method: $5/sq ft up to 300 sq ft).',
                'category' => 'deductions',
                'priority' => 'medium',
                'potential_savings' => 1500 * ($taxData['tax_rate'] / 100), // Max $1,500 deduction
            ];
        }

        // Check for vehicle deduction
        $hasVehicleDeduction = collect($taxData['deduction_categories'])
            ->where('category_name', 'Vehicle Expenses')
            ->isNotEmpty();

        if (!$hasVehicleDeduction && $taxData['total_revenue'] > 0) {
            $recommendations[] = [
                'title' => 'Vehicle Expense Deduction',
                'description' => 'Track business mileage and claim vehicle expenses (65.5¢/mile for 2023).',
                'category' => 'deductions',
                'priority' => 'low',
                'potential_savings' => 3000 * ($taxData['tax_rate'] / 100), // Estimated savings
            ];
        }

        // Year-end timing strategy
        if (now()->month >= 10 && $taxData['taxable_income'] > 50000) {
            $recommendations[] = [
                'title' => 'Year-End Tax Planning',
                'description' => 'Consider accelerating expenses into this year or deferring income to next year to manage tax liability.',
                'category' => 'timing',
                'priority' => 'high',
                'potential_savings' => $taxData['taxable_income'] * 0.02,
            ];
        }

        return $recommendations;
    }

    protected function estimatePotentialSavings(array $taxData): array
    {
        $recommendations = $this->getRuleBasedRecommendations($taxData);

        $totalPotential = collect($recommendations)
            ->sum('potential_savings');

        return [
            'total_potential_savings' => round($totalPotential, 2),
            'estimated_range_min' => round($totalPotential * 0.5, 2),
            'estimated_range_max' => round($totalPotential * 1.5, 2),
            'confidence_level' => 'moderate',
        ];
    }

    protected function calculateOptimizationScore(array $taxData): array
    {
        $score = 100; // Start with perfect score

        // Deduct points for missed opportunities
        if ($taxData['total_deductions'] < ($taxData['total_revenue'] * 0.15)) {
            $score -= 20; // Low deduction rate
        }

        if ($taxData['business_type'] === 'sole_proprietor' && $taxData['total_revenue'] > 100000) {
            $score -= 15; // Could benefit from entity restructuring
        }

        $deductionCategories = count($taxData['deduction_categories']);
        if ($deductionCategories < 3) {
            $score -= 10; // Limited deduction categories
        }

        // Bonus points for good practices
        if ($taxData['total_deductions'] > ($taxData['total_revenue'] * 0.25) && $taxData['total_deductions'] < ($taxData['total_revenue'] * 0.45)) {
            $score += 5; // Healthy deduction rate
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->getGrade($score),
            'message' => $this->getScoreMessage($score),
        ];
    }

    protected function getGrade(int $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    protected function getScoreMessage(int $score): string
    {
        if ($score >= 90) {
            return 'Excellent! Your tax strategy is well optimized.';
        } elseif ($score >= 80) {
            return 'Good tax optimization with room for minor improvements.';
        } elseif ($score >= 70) {
            return 'Moderate optimization. Several opportunities available.';
        } elseif ($score >= 60) {
            return 'Significant optimization opportunities exist.';
        }

        return 'Major tax savings potential. Consider professional consultation.';
    }
}
