<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            @php
                $assessment = $this->getRiskAssessment();
            @endphp

            @if($assessment)
                <!-- Risk Score -->
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Company Risk Score</h3>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $assessment->risk_score }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full {{ $assessment->risk_color === 'success' ? 'bg-success-500' : ($assessment->risk_color === 'warning' ? 'bg-warning-500' : 'bg-danger-500') }}"
                                 style="width: {{ $assessment->risk_score }}%">
                            </div>
                        </div>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 capitalize">
                            {{ $assessment->risk_level }} risk
                        </span>
                    </div>
                </div>

                <!-- Loan Worthiness Gauge -->
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Loan Worthiness</h3>
                    <div class="flex items-center justify-center">
                        <div class="relative w-32 h-32">
                            <svg class="transform -rotate-90 w-32 h-32">
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    class="text-gray-200 dark:text-gray-700"
                                />
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $this->getLoanWorthinessPercentage() / 100) }}"
                                    class="{{ $this->getLoanWorthinessColor() === 'success' ? 'text-success-500' : ($this->getLoanWorthinessColor() === 'warning' ? 'text-warning-500' : 'text-danger-500') }}"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getLoanWorthinessPercentage() }}</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400 capitalize">{{ $assessment->loan_worthiness_level }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Risk Factors -->
                @if($assessment->risk_factors && count($assessment->risk_factors) > 0)
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Risk Factors</h3>
                        <ul class="space-y-1">
                            @foreach($assessment->risk_factors as $factor)
                                <li class="text-xs text-gray-600 dark:text-gray-400 flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>{{ $factor }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No risk assessment data available</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
