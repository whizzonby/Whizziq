<x-filament-panels::page>
    {{-- Current Usage Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Today's Usage --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Today's Usage</h3>
                <x-heroicon-o-bolt class="w-6 h-6 text-primary-500" />
            </div>
            <div class="space-y-2">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $canUse['today_usage'] ?? 0 }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        / {{ $planLimits['daily_limit'] }} requests
                    </span>
                </div>
                @if($canUse['allowed'])
                    <p class="text-sm text-success-600 dark:text-success-400">
                        ✓ {{ $canUse['remaining'] }} requests remaining
                    </p>
                @else
                    <p class="text-sm text-danger-600 dark:text-danger-400">
                        ⚠ Daily limit reached - Resets at midnight
                    </p>
                @endif
                <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        class="h-2 rounded-full transition-all {{ $canUse['today_usage'] >= $planLimits['daily_limit'] * 0.9 ? 'bg-danger-500' : ($canUse['today_usage'] >= $planLimits['daily_limit'] * 0.7 ? 'bg-warning-500' : 'bg-success-500') }}"
                        style="width: {{ min(100, ($canUse['today_usage'] / max($planLimits['daily_limit'], 1)) * 100) }}%"
                    ></div>
                </div>
            </div>
        </div>

        {{-- Current Plan --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Current Plan</h3>
                <x-heroicon-o-sparkles class="w-6 h-6 text-warning-500" />
            </div>
            <div class="space-y-3">
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ $planLimits['plan_name'] }} Plan
                    </span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-check-circle class="w-4 h-4 text-success-500" />
                        <span class="text-gray-700 dark:text-gray-300">{{ $planLimits['daily_limit'] }} daily AI requests</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($planLimits['daily_document_analysis_limit'] >= 999)
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500" />
                            <span class="text-gray-700 dark:text-gray-300">Unlimited document analysis</span>
                        @else
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500" />
                            <span class="text-gray-700 dark:text-gray-300">{{ $planLimits['daily_document_analysis_limit'] }} docs/day</span>
                        @endif
                    </div>
                    @if($planLimits['has_task_extraction'])
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-success-500" />
                            <span class="text-gray-700 dark:text-gray-300">Task extraction</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Period Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($selectedPeriod) }} Stats</h3>
                <x-heroicon-o-chart-bar class="w-6 h-6 text-info-500" />
            </div>
            <div class="space-y-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Requests</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($usageStats['total_requests']) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Estimated Cost</p>
                    <p class="text-xl font-semibold text-gray-700 dark:text-gray-300">
                        ${{ number_format($usageStats['total_cost_dollars'], 2) }}
                    </p>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format($usageStats['total_tokens']) }} tokens used
                </p>
            </div>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">View Period:</h3>
            <div class="flex gap-2">
                @foreach(['today', 'week', 'month', 'quarter', 'year'] as $period)
                    <button
                        wire:click="changePeriod('{{ $period }}')"
                        class="px-3 py-1 text-sm rounded-md transition-colors {{ $selectedPeriod === $period ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        {{ ucfirst($period) }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Usage by Feature --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Usage by Feature</h3>

        @if($usageStats && count($usageStats['by_feature']) > 0)
            <div class="space-y-4">
                @foreach($usageStats['by_feature'] as $feature)
                    @php
                        $percentage = $usageStats['total_requests'] > 0
                            ? ($feature->count / $usageStats['total_requests']) * 100
                            : 0;
                        $featureName = match($feature->feature) {
                            'email_generation' => 'Email Generation',
                            'document_analysis' => 'Document Analysis',
                            'business_insights' => 'Business Insights',
                            'task_extraction' => 'Task Extraction',
                            'auto_categorization' => 'Auto Categorization',
                            'marketing_insights' => 'Marketing Insights',
                            default => ucwords(str_replace('_', ' ', $feature->feature)),
                        };
                    @endphp

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $featureName }}
                            </span>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($feature->count) }} requests
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                    ({{ number_format($percentage, 1) }}%)
                                </span>
                            </div>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-primary-500 h-2 rounded-full transition-all"
                                style="width: {{ $percentage }}%"
                            ></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ number_format($feature->tokens) }} tokens</span>
                            <span>${{ number_format($feature->cost_cents / 100, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <x-heroicon-o-chart-bar class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No AI usage data for this period</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                    Start using AI features to see analytics here
                </p>
            </div>
        @endif
    </div>

    {{-- Upgrade CTA (if not on premium) --}}
    @if($planLimits['plan_name'] !== 'Premium')
        <div class="mt-6 bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="bg text-xl font-bold mb-2">Need More AI Power?</h3> 
                    <p class="text-primary-100">
                        Upgrade to {{ $planLimits['plan_name'] === 'Basic' ? 'Pro' : 'Premium' }} for
                        {{ $planLimits['plan_name'] === 'Basic' ? '75' : '200' }} daily AI requests and advanced features
                    </p>
                </div>
                <a
                    href="{{ route('filament.dashboard.resources.subscriptions.index') }}"
                    class="px-6 py-3 bg-white text-primary-600 rounded-lg font-semibold hover:bg-primary-50 transition-colors"
                >
                    Upgrade Now
                </a>
            </div>
        </div>
    @endif
</x-filament-panels::page>
