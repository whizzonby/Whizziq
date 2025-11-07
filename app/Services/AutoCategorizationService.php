<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AutoCategorizationService
{
    /**
     * Auto-categorize expenses for a user
     */
    public function autoCategorizeExpenses(User $user): array
    {
        $uncategorizedExpenses = Expense::where('user_id', $user->id)
            ->whereNull('tax_category_id')
            ->get();
        
        $results = [
            'processed' => 0,
            'categorized' => 0,
            'uncategorized' => 0,
            'categories' => [],
        ];
        
        foreach ($uncategorizedExpenses as $expense) {
            $results['processed']++;
            
            $category = $this->categorizeExpense($expense);
            
            if ($category) {
                $expense->update(['tax_category_id' => $category->id]);
                $results['categorized']++;
                
                if (!isset($results['categories'][$category->name])) {
                    $results['categories'][$category->name] = 0;
                }
                $results['categories'][$category->name]++;
            } else {
                $results['uncategorized']++;
            }
        }
        
        return $results;
    }

    /**
     * Categorize a single expense
     */
    public function categorizeExpense(Expense $expense): ?TaxCategory
    {
        $description = strtolower($expense->description ?? '');
        $amount = $expense->amount;
        
        // Rule-based categorization
        $category = $this->getCategoryByRules($description, $amount);
        
        if ($category) {
            return $category;
        }
        
        // AI-powered categorization (if available)
        $category = $this->getCategoryByAI($description, $amount);
        
        return $category;
    }

    /**
     * Get category by business rules
     */
    protected function getCategoryByRules(string $description, float $amount): ?TaxCategory
    {
        // Office Supplies & Equipment
        if ($this->containsAny($description, ['office', 'supplies', 'stationery', 'paper', 'pens', 'pencils', 'stapler', 'folder', 'binder'])) {
            return TaxCategory::where('name', 'Office Supplies')->first();
        }
        
        // Computer & Software
        if ($this->containsAny($description, ['computer', 'laptop', 'software', 'subscription', 'adobe', 'microsoft', 'google', 'slack', 'zoom', 'notion'])) {
            return TaxCategory::where('name', 'Computer & Software')->first();
        }
        
        // Travel & Transportation
        if ($this->containsAny($description, ['travel', 'flight', 'hotel', 'uber', 'lyft', 'taxi', 'gas', 'fuel', 'parking', 'toll', 'mileage'])) {
            return TaxCategory::where('name', 'Travel & Transportation')->first();
        }
        
        // Meals & Entertainment
        if ($this->containsAny($description, ['restaurant', 'lunch', 'dinner', 'coffee', 'starbucks', 'food', 'meal', 'entertainment', 'client', 'business meal'])) {
            return TaxCategory::where('name', 'Meals & Entertainment')->first();
        }
        
        // Marketing & Advertising
        if ($this->containsAny($description, ['marketing', 'advertising', 'facebook', 'google ads', 'instagram', 'linkedin', 'twitter', 'promotion', 'campaign', 'seo'])) {
            return TaxCategory::where('name', 'Marketing & Advertising')->first();
        }
        
        // Professional Services
        if ($this->containsAny($description, ['lawyer', 'attorney', 'legal', 'accountant', 'bookkeeper', 'consultant', 'advisor', 'professional', 'service'])) {
            return TaxCategory::where('name', 'Professional Services')->first();
        }
        
        // Rent & Utilities
        if ($this->containsAny($description, ['rent', 'office rent', 'utilities', 'electric', 'water', 'internet', 'phone', 'cable', 'wifi', 'co-working'])) {
            return TaxCategory::where('name', 'Rent & Utilities')->first();
        }
        
        // Insurance
        if ($this->containsAny($description, ['insurance', 'liability', 'business insurance', 'professional liability', 'health insurance'])) {
            return TaxCategory::where('name', 'Insurance')->first();
        }
        
        // Training & Education
        if ($this->containsAny($description, ['training', 'course', 'education', 'seminar', 'workshop', 'conference', 'certification', 'learning'])) {
            return TaxCategory::where('name', 'Training & Education')->first();
        }
        
        // Vehicle Expenses
        if ($this->containsAny($description, ['car', 'vehicle', 'auto', 'maintenance', 'repair', 'registration', 'license', 'dmv'])) {
            return TaxCategory::where('name', 'Vehicle Expenses')->first();
        }
        
        // Equipment & Furniture
        if ($this->containsAny($description, ['equipment', 'furniture', 'desk', 'chair', 'monitor', 'printer', 'camera', 'phone', 'tablet'])) {
            return TaxCategory::where('name', 'Equipment & Furniture')->first();
        }
        
        // Bank & Finance
        if ($this->containsAny($description, ['bank', 'fee', 'interest', 'loan', 'credit card', 'payment processing', 'stripe', 'paypal'])) {
            return TaxCategory::where('name', 'Bank & Finance')->first();
        }
        
        // Subscriptions & Memberships
        if ($this->containsAny($description, ['subscription', 'membership', 'annual', 'monthly', 'yearly', 'recurring', 'saas'])) {
            return TaxCategory::where('name', 'Subscriptions & Memberships')->first();
        }
        
        return null;
    }

    /**
     * Get category by AI (placeholder for future AI integration)
     */
    protected function getCategoryByAI(string $description, float $amount): ?TaxCategory
    {
        // This would integrate with AI services like OpenAI
        // For now, return null to fall back to manual categorization
        return null;
    }

    /**
     * Check if description contains any of the keywords
     */
    protected function containsAny(string $description, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($description, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get categorization suggestions for manual review
     */
    public function getCategorizationSuggestions(User $user): array
    {
        $uncategorizedExpenses = Expense::where('user_id', $user->id)
            ->whereNull('tax_category_id')
            ->get();
        
        $suggestions = [];
        
        foreach ($uncategorizedExpenses as $expense) {
            $suggestions[] = [
                'expense_id' => $expense->id,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'suggested_category' => $this->categorizeExpense($expense),
                'confidence' => $this->getConfidenceLevel($expense),
            ];
        }
        
        return $suggestions;
    }

    /**
     * Get confidence level for categorization
     */
    protected function getConfidenceLevel(Expense $expense): string
    {
        $description = strtolower($expense->description ?? '');
        
        // High confidence keywords
        $highConfidence = ['office supplies', 'software', 'travel', 'restaurant', 'marketing'];
        foreach ($highConfidence as $keyword) {
            if (str_contains($description, $keyword)) {
                return 'high';
            }
        }
        
        // Medium confidence keywords
        $mediumConfidence = ['subscription', 'fee', 'service', 'equipment'];
        foreach ($mediumConfidence as $keyword) {
            if (str_contains($description, $keyword)) {
                return 'medium';
            }
        }
        
        return 'low';
    }

    /**
     * Bulk categorize expenses
     */
    public function bulkCategorize(User $user, array $categorizations): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
        ];
        
        foreach ($categorizations as $categorization) {
            $results['processed']++;
            
            try {
                $expense = Expense::find($categorization['expense_id']);
                if ($expense && $expense->user_id === $user->id) {
                    $expense->update(['tax_category_id' => $categorization['category_id']]);
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error("Failed to categorize expense {$categorization['expense_id']}: " . $e->getMessage());
            }
        }
        
        return $results;
    }

    /**
     * Get categorization statistics
     */
    public function getCategorizationStats(User $user): array
    {
        $totalExpenses = Expense::where('user_id', $user->id)->count();
        $categorizedExpenses = Expense::where('user_id', $user->id)
            ->whereNotNull('tax_category_id')
            ->count();
        
        $uncategorizedExpenses = $totalExpenses - $categorizedExpenses;
        $categorizationRate = $totalExpenses > 0 ? ($categorizedExpenses / $totalExpenses) * 100 : 0;
        
        return [
            'total_expenses' => $totalExpenses,
            'categorized_expenses' => $categorizedExpenses,
            'uncategorized_expenses' => $uncategorizedExpenses,
            'categorization_rate' => round($categorizationRate, 2),
            'needs_attention' => $uncategorizedExpenses > 0,
        ];
    }

    /**
     * Auto-categorize new expenses on creation
     */
    public function autoCategorizeNewExpense(Expense $expense): void
    {
        if ($expense->tax_category_id) {
            return; // Already categorized
        }
        
        $category = $this->categorizeExpense($expense);
        
        if ($category) {
            $expense->update(['tax_category_id' => $category->id]);
        }
    }
}
