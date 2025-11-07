<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-sparkles"
                        class="h-5 w-5 text-primary-500"
                    />
                    <span class="font-bold">AI Business Intelligence</span>
                </div>
                <x-filament::button
                    wire:click="refreshInsights"
                    wire:loading.attr="disabled"
                    size="sm"
                    color="gray"
                    outlined
                >
                    <x-filament::icon
                        icon="heroicon-m-arrow-path"
                        class="h-4 w-4"
                        wire:loading.class="animate-spin"
                        wire:target="refreshInsights"
                    />
                    Refresh
                </x-filament::button>
            </div>
        </x-slot>

        @if($isLoading)
            <div class="text-center py-12">
                <x-filament::loading-indicator class="h-10 w-10 mx-auto"/>
                <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Analyzing your business data...</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">This may take a few moments</p>
            </div>
        @else
            <div class="space-y-6">
                {{-- Anomaly Alerts Section --}}
                @if($anomalies && count($anomalies) > 0)
                    <div class="border-l-4 border-danger-500 bg-danger-50 dark:bg-danger-950 p-4 rounded-r-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <x-filament::icon
                                icon="heroicon-o-exclamation-triangle"
                                class="h-5 w-5 text-danger-600 dark:text-danger-400"
                            />
                            <h3 class="text-sm font-bold text-danger-900 dark:text-danger-100">
                                Performance Alerts
                            </h3>
                        </div>
                        <div class="space-y-2">
                            @foreach($anomalies as $anomaly)
                                <div class="flex items-start gap-2 text-sm">
                                    <x-filament::badge :color="getSeverityColor($anomaly['severity'])">
                                        {{ ucfirst($anomaly['severity']) }}
                                    </x-filament::badge>
                                    <p class="text-danger-800 dark:text-danger-200 flex-1">
                                        <span class="font-semibold">{{ $anomaly['metric'] }}:</span>
                                        {{ $anomaly['message'] }}
                                        @if(isset($anomaly['deviation']))
                                            <span class="text-xs">({{ number_format(abs($anomaly['deviation']), 1) }}% deviation)</span>
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Forecasts Section --}}
                @if($forecasts && count($forecasts) > 0)
                    <div class="border-l-4 border-primary-500 bg-primary-50 dark:bg-primary-950 p-4 rounded-r-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <x-filament::icon
                                icon="heroicon-o-chart-bar"
                                class="h-5 w-5 text-primary-600 dark:text-primary-400"
                            />
                            <h3 class="text-sm font-bold text-primary-900 dark:text-primary-100">
                                AI Forecasts
                            </h3>
                        </div>
                        <div class="grid gap-3">
                            @foreach($forecasts as $forecast)
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-primary-200 dark:border-primary-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $forecast['metric'] }} - {{ $forecast['period'] }}
                                        </span>
                                        <x-filament::badge color="info">
                                            {{ $forecast['confidence'] }}% confidence
                                        </x-filament::badge>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Current:</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                ${{ number_format($forecast['current_value'], 0) }}
                                            </span>
                                        </div>
                                        <x-filament::icon
                                            :icon="$forecast['trend'] === 'increasing' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                            :class="$forecast['trend'] === 'increasing' ? 'text-success-500' : 'text-danger-500'"
                                            class="h-5 w-5"
                                        />
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Forecast:</span>
                                            <span class="font-medium" :class="$forecast['trend'] === 'increasing' ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'">
                                                ${{ number_format($forecast['forecast_value'], 0) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- AI Insights Section --}}
                @if($insights && count($insights) > 0)
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <x-filament::icon
                                icon="heroicon-o-light-bulb"
                                class="h-5 w-5 text-warning-500"
                            />
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                Actionable Insights & Recommendations
                            </h3>
                        </div>
                        <div class="space-y-3">
                            @foreach($insights as $insight)
                                <div class="flex items-start gap-3 p-4 rounded-lg border transition-all hover:shadow-md
                                    @if($insight['type'] === 'warning')
                                        bg-warning-50 dark:bg-warning-950 border-warning-200 dark:border-warning-800
                                    @elseif($insight['type'] === 'success')
                                        bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800
                                    @elseif($insight['type'] === 'danger')
                                        bg-danger-50 dark:bg-danger-950 border-danger-200 dark:border-danger-800
                                    @elseif($insight['type'] === 'info')
                                        bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800
                                    @else
                                        bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700
                                    @endif
                                ">
                                    <x-filament::icon
                                        :icon="$insight['icon']"
                                        class="h-6 w-6 flex-shrink-0 mt-0.5
                                            @if($insight['type'] === 'warning')
                                                text-warning-600 dark:text-warning-400
                                            @elseif($insight['type'] === 'success')
                                                text-success-600 dark:text-success-400
                                            @elseif($insight['type'] === 'danger')
                                                text-danger-600 dark:text-danger-400
                                            @elseif($insight['type'] === 'info')
                                                text-info-600 dark:text-info-400
                                            @else
                                                text-gray-600 dark:text-gray-400
                                            @endif
                                        "
                                    />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold leading-tight
                                            @if($insight['type'] === 'warning')
                                                text-warning-900 dark:text-warning-100
                                            @elseif($insight['type'] === 'success')
                                                text-success-900 dark:text-success-100
                                            @elseif($insight['type'] === 'danger')
                                                text-danger-900 dark:text-danger-100
                                            @elseif($insight['type'] === 'info')
                                                text-info-900 dark:text-info-100
                                            @else
                                                text-gray-900 dark:text-gray-100
                                            @endif
                                        ">
                                            {{ $insight['title'] }}
                                        </h4>
                                        <p class="mt-1.5 text-sm leading-relaxed
                                            @if($insight['type'] === 'warning')
                                                text-warning-800 dark:text-warning-200
                                            @elseif($insight['type'] === 'success')
                                                text-success-800 dark:text-success-200
                                            @elseif($insight['type'] === 'danger')
                                                text-danger-800 dark:text-danger-200
                                            @elseif($insight['type'] === 'info')
                                                text-info-800 dark:text-info-200
                                            @else
                                                text-gray-800 dark:text-gray-200
                                            @endif
                                        ">
                                            {{ trim($insight['description']) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-filament::icon
                            icon="heroicon-o-information-circle"
                            class="h-12 w-12 mx-auto text-gray-400"
                        />
                        <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">
                            No insights available
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                            Add business metrics to get AI-powered insights
                        </p>
                    </div>
                @endif

                {{-- Footer Note --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-information-circle" class="h-3 w-3"/>
                        Insights are generated using AI and cached for 1 hour. Click refresh for latest analysis.
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
