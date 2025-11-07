<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AIUsageService
{
    // Plan limits configuration
    const LIMITS = [
        'basic' => [
            'daily_limit' => 20,
            'daily_document_analysis_limit' => 5,
            'has_task_extraction' => false,
            'has_auto_categorization' => false,
            'has_marketing_insights' => false,
            'has_advanced_features' => false,
        ],
        'pro' => [
            'daily_limit' => 75,
            'daily_document_analysis_limit' => 20,
            'has_task_extraction' => true,
            'has_auto_categorization' => true,
            'has_marketing_insights' => true,
            'has_advanced_features' => false,
        ],
        'premium' => [
            'daily_limit' => 200,
            'daily_document_analysis_limit' => 999, // Unlimited
            'has_task_extraction' => true,
            'has_auto_categorization' => true,
            'has_marketing_insights' => true,
            'has_advanced_features' => true,
        ],
    ];

    /**
     * Check if user can make AI request
     */
    public function canMakeRequest(User $user, string $feature = 'general'): array
    {
        // Admins and owners are unlimited
        if ($this->isUnlimitedUser($user)) {
            return [
                'allowed' => true,
                'remaining' => 999999,
                'limit' => 999999,
                'reset_at' => null,
            ];
        }

        // Get limits from subscription metadata (simple feature system)
        $metadata = $user->subscriptionProductMetadata();
        
        // Default limits if no subscription (safety fallback)
        $dailyLimit = !empty($metadata) 
            ? (int)($metadata['ai_daily_limit'] ?? 20) 
            : 20;
        
        $documentLimit = !empty($metadata)
            ? (int)($metadata['ai_document_analysis_limit'] ?? 5)
            : 5;

        // Check feature-specific permissions using metadata
        if (!$this->hasFeatureAccess($user, $metadata, $feature)) {
            return [
                'allowed' => false,
                'reason' => 'feature_not_available',
                'message' => "This feature is not available on your current plan. Please upgrade to access {$feature}.",
                'upgrade_required' => true,
            ];
        }

        // Check daily limit
        $todayUsage = $this->getTodayUsage($user);

        // Check document analysis specific limit
        if ($feature === 'document_analysis') {
            $documentUsage = $this->getTodayUsageByFeature($user, 'document_analysis');

            // 999 means unlimited in metadata
            if ($documentLimit < 999 && $documentUsage >= $documentLimit) {
                return [
                    'allowed' => false,
                    'reason' => 'document_limit_reached',
                    'message' => "You've reached your daily document analysis limit ({$documentLimit}). Resets at midnight.",
                    'remaining' => 0,
                    'limit' => $documentLimit,
                    'reset_at' => Carbon::tomorrow()->startOfDay(),
                ];
            }
        }

        // Check general daily limit (unlimited = 999999 or very high number)
        $effectiveLimit = ($dailyLimit === 999 || $dailyLimit >= 999999) ? 999999 : $dailyLimit;
        
        if ($effectiveLimit < 999999 && $todayUsage >= $effectiveLimit) {
            return [
                'allowed' => false,
                'reason' => 'daily_limit_reached',
                'message' => "You've reached your daily AI request limit ({$dailyLimit}). Resets at midnight.",
                'remaining' => 0,
                'limit' => $dailyLimit,
                'reset_at' => Carbon::tomorrow()->startOfDay(),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $effectiveLimit < 999999 ? ($effectiveLimit - $todayUsage) : 999999,
            'limit' => $dailyLimit,
            'reset_at' => Carbon::tomorrow()->startOfDay(),
        ];
    }

    /**
     * Log AI usage
     */
    public function logUsage(
        User $user,
        string $feature,
        ?string $action = null,
        int $tokensUsed = 0,
        ?string $promptSummary = null,
        ?array $metadata = null
    ): void {
        // Don't log for unlimited users (but still track for analytics)
        $costCents = $this->estimateCost($tokensUsed);

        DB::table('ai_usage_logs')->insert([
            'user_id' => $user->id,
            'feature' => $feature,
            'action' => $action,
            'tokens_used' => $tokensUsed,
            'cost_cents' => $costCents,
            'prompt_summary' => $promptSummary ? substr($promptSummary, 0, 200) : null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Clear cache for this user's usage
        Cache::forget("ai_usage_today_{$user->id}");
        Cache::forget("ai_usage_feature_today_{$user->id}_{$feature}");
    }

    /**
     * Get today's total usage for user
     */
    public function getTodayUsage(User $user): int
    {
        return Cache::remember("ai_usage_today_{$user->id}", 300, function () use ($user) {
            return DB::table('ai_usage_logs')
                ->where('user_id', $user->id)
                ->whereDate('requested_at', Carbon::today())
                ->count();
        });
    }

    /**
     * Get today's usage for specific feature
     */
    public function getTodayUsageByFeature(User $user, string $feature): int
    {
        return Cache::remember("ai_usage_feature_today_{$user->id}_{$feature}", 300, function () use ($user, $feature) {
            return DB::table('ai_usage_logs')
                ->where('user_id', $user->id)
                ->where('feature', $feature)
                ->whereDate('requested_at', Carbon::today())
                ->count();
        });
    }

    /**
     * Get usage statistics for user
     */
    public function getUsageStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::today()->startOfMonth();
        $endDate = $endDate ?? Carbon::now();

        $stats = DB::table('ai_usage_logs')
            ->where('user_id', $user->id)
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_cents) as total_cost_cents,
                feature,
                DATE(requested_at) as date
            ')
            ->groupBy('feature', 'date')
            ->orderBy('date', 'desc')
            ->get();

        $byFeature = DB::table('ai_usage_logs')
            ->where('user_id', $user->id)
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->selectRaw('
                feature,
                COUNT(*) as count,
                SUM(tokens_used) as tokens,
                SUM(cost_cents) as cost_cents
            ')
            ->groupBy('feature')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'timeline' => $stats,
            'by_feature' => $byFeature,
            'total_requests' => $stats->sum('total_requests'),
            'total_tokens' => $stats->sum('total_tokens'),
            'total_cost_cents' => $stats->sum('total_cost_cents'),
            'total_cost_dollars' => $stats->sum('total_cost_cents') / 100,
        ];
    }

    /**
     * Check if user has access to specific feature using metadata
     */
    protected function hasFeatureAccess(User $user, array $metadata, string $feature): bool
    {
        // If no metadata, no access (user needs subscription)
        if (empty($metadata)) {
            return false;
        }

        return match ($feature) {
            'email_generation' => $user->hasFeature('ai_email_features'),
            'task_extraction' => !empty($metadata['ai_task_features']) && in_array(strtolower($metadata['ai_task_features']), ['true', '1', 'yes']),
            'document_analysis' => !empty($metadata['documents_ai_analysis']) && in_array(strtolower($metadata['documents_ai_analysis']), ['true', '1', 'yes']),
            'swot_analysis' => !empty($metadata['ai_swot_risk']) && in_array(strtolower($metadata['ai_swot_risk']), ['true', '1', 'yes']),
            'business_insights' => !empty($metadata['ai_business_insights']) && in_array(strtolower($metadata['ai_business_insights']), ['true', '1', 'yes']),
            'financial_forecast' => !empty($metadata['ai_forecasting']) && in_array(strtolower($metadata['ai_forecasting']), ['true', '1', 'yes']),
            'anomaly_detection' => !empty($metadata['ai_anomaly_detection']) && in_array(strtolower($metadata['ai_anomaly_detection']), ['true', '1', 'yes']),
            default => true, // General AI features available if user has subscription
        };
    }

    /**
     * Check if user is unlimited (admin/owner)
     */
    protected function isUnlimitedUser(User $user): bool
    {
        // Check if user is admin
        if (method_exists($user, 'is_admin') && $user->is_admin) {
            return true;
        }

        // Check if user has admin role
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Check roles relationship
        if ($user->relationLoaded('roles')) {
            return $user->roles->contains('name', 'admin');
        }

        return false;
    }

    /**
     * Get user's plan name from subscription
     */
    protected function getUserPlanName(User $user): string
    {
        // Try to get from active subscription
        if ($user->relationLoaded('subscriptions')) {
            $activeSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->first();

            if ($activeSubscription && $activeSubscription->relationLoaded('plan')) {
                $planSlug = $activeSubscription->plan->slug ?? '';

                if (str_contains(strtolower($planSlug), 'premium')) {
                    return 'premium';
                } elseif (str_contains(strtolower($planSlug), 'pro')) {
                    return 'pro';
                } elseif (str_contains(strtolower($planSlug), 'basic')) {
                    return 'basic';
                }
            }
        }

        // Default to basic if no subscription found
        return 'basic';
    }

    /**
     * Estimate cost in cents based on tokens
     * GPT-4: ~$0.03 per 1K input tokens, ~$0.06 per 1K output tokens
     * Using average of $0.045 per 1K tokens
     */
    protected function estimateCost(int $tokens): int
    {
        if ($tokens === 0) {
            return 0;
        }

        // $0.045 per 1000 tokens = 4.5 cents per 1000 tokens
        return (int) ceil(($tokens / 1000) * 4.5);
    }

    /**
     * Get plan limits for a user from metadata
     */
    public function getPlanLimits(User $user): array
    {
        $metadata = $user->subscriptionProductMetadata();
        
        if (empty($metadata)) {
            // Default limits if no subscription
            return [
                'daily_limit' => 20,
                'daily_document_analysis_limit' => 5,
                'has_task_extraction' => false,
                'has_auto_categorization' => false,
                'has_marketing_insights' => false,
                'has_advanced_features' => false,
            ];
        }
        
        // Helper to check boolean metadata values
        $checkFeature = function($key) use ($metadata) {
            if (!isset($metadata[$key])) {
                return false;
            }
            $value = strtolower($metadata[$key]);
            return in_array($value, ['true', '1', 'yes']);
        };
        
        return [
            'daily_limit' => (int)($metadata['ai_daily_limit'] ?? 20),
            'daily_document_analysis_limit' => (int)($metadata['ai_document_analysis_limit'] ?? 5),
            'has_task_extraction' => $checkFeature('ai_task_features'),
            'has_auto_categorization' => $checkFeature('ai_auto_categorization'),
            'has_marketing_insights' => $checkFeature('ai_marketing_insights'),
            'has_advanced_features' => $checkFeature('ai_swot_risk') || $checkFeature('ai_forecasting') || $checkFeature('ai_anomaly_detection'),
        ];
    }

    /**
     * Get all plan limits (for comparison) - kept for backward compatibility
     */
    public function getAllPlanLimits(): array
    {
        return self::LIMITS;
    }
}
