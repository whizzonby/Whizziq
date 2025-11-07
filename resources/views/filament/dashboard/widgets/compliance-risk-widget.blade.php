@php
    $risk = $this->getRiskAssessment();
    $riskColor = match($risk['risk_level']) {
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'info',
        'minimal' => 'success',
        default => 'gray',
    };
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Compliance Risk Assessment</h3>
                <x-filament::badge :color="$riskColor" size="lg">
                    {{ ucfirst($risk['risk_level']) }} Risk
                </x-filament::badge>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Risk Score Gauge --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-center">
                        <div class="text-4xl font-bold {{ $risk['risk_percentage'] >= 60 ? 'text-red-600' : ($risk['risk_percentage'] >= 35 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $risk['risk_percentage'] }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Risk Score</div>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Audit Probability: {{ $risk['audit_probability'] }}
                        </div>
                    </div>
                </div>

                {{-- Risk Factors --}}
                <div class="space-y-2">
                    @foreach($risk['factors'] as $key => $factor)
                        <div class="flex items-center justify-between p-2 rounded {{ $factor['status'] === 'high_risk' ? 'bg-red-50 dark:bg-red-900/20' : ($factor['status'] === 'medium_risk' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-green-50 dark:bg-green-900/20') }}">
                            <span class="text-sm">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                            <x-filament::badge :color="$factor['status'] === 'high_risk' ? 'danger' : ($factor['status'] === 'medium_risk' ? 'warning' : 'success')" size="sm">
                                {{ $factor['score'] }}/{{ $factor['weight'] }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recommendations --}}
            @if(!empty($risk['recommendations']))
                <div class="mt-4 border-t pt-4">
                    <h4 class="font-semibold text-sm mb-2">Recommendations:</h4>
                    <ul class="space-y-1">
                        @foreach($risk['recommendations'] as $recommendation)
                            <li class="text-sm text-gray-600 dark:text-gray-400 flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $recommendation }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
