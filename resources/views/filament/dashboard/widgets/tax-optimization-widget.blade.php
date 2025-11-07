@php
    $optimizations = $this->getOptimizations();
    $score = $optimizations['optimization_score'] ?? ['score' => 0, 'grade' => 'F', 'message' => ''];
    $savings = $optimizations['estimated_savings'] ?? [];
    $quickWins = $optimizations['quick_wins'] ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Tax Optimization Recommendations</h3>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="success" size="lg">
                        Grade: {{ $score['grade'] }}
                    </x-filament::badge>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Score: {{ $score['score'] }}/100
                    </span>
                </div>
            </div>

            {{-- Potential Savings --}}
            @if(!empty($savings))
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-green-800 dark:text-green-300 font-medium">Estimated Tax Savings Potential</div>
                            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                ${{ number_format($savings['total_potential_savings'] ?? 0, 2) }}
                            </div>
                            <div class="text-xs text-green-700 dark:text-green-400 mt-1">
                                Range: ${{ number_format($savings['estimated_range_min'] ?? 0) }} - ${{ number_format($savings['estimated_range_max'] ?? 0) }}
                            </div>
                        </div>
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            @endif

            {{-- Quick Wins --}}
            <div class="space-y-3">
                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">Quick Win Opportunities:</h4>
                @forelse($quickWins as $recommendation)
                    <div class="border rounded-lg p-4 {{ $recommendation['priority'] === 'high' ? 'border-orange-300 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h5 class="font-medium text-sm">{{ $recommendation['title'] }}</h5>
                                    <x-filament::badge
                                        :color="$recommendation['priority'] === 'high' ? 'danger' : ($recommendation['priority'] === 'medium' ? 'warning' : 'info')"
                                        size="sm"
                                    >
                                        {{ ucfirst($recommendation['priority']) }}
                                    </x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $recommendation['description'] }}
                                </p>
                                @if(isset($recommendation['potential_savings']) && $recommendation['potential_savings'] > 0)
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-2 font-medium">
                                        💰 Potential Savings: ${{ number_format($recommendation['potential_savings'], 2) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm">Your tax strategy is well optimized!</p>
                    </div>
                @endforelse
            </div>

            {{-- AI Recommendations --}}
            @if(isset($optimizations['ai_recommendations']) && !empty($optimizations['ai_recommendations']))
                <div class="border-t pt-4 mt-4">
                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z"></path>
                        </svg>
                        AI-Powered Recommendations:
                    </h4>
                    <div class="space-y-2">
                        @foreach($optimizations['ai_recommendations'] as $aiRec)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-3 border border-blue-200 dark:border-blue-800">
                                <div class="font-medium text-sm text-blue-900 dark:text-blue-100">{{ $aiRec['title'] }}</div>
                                <div class="text-sm text-blue-800 dark:text-blue-300 mt-1">{{ $aiRec['description'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
