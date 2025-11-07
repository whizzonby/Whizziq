<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-shield-exclamation"
                        class="h-5 w-5 text-danger-500"
                    />
                    <span>Anomaly Detection</span>
                </div>
                <x-filament::button
                    wire:click="refreshAnomalies"
                    wire:loading.attr="disabled"
                    size="sm"
                    color="gray"
                    outlined
                >
                    <x-filament::icon
                        icon="heroicon-m-arrow-path"
                        class="h-4 w-4"
                        wire:loading.class="animate-spin"
                        wire:target="refreshAnomalies"
                    />
                    Scan
                </x-filament::button>
            </div>
        </x-slot>

        <div class="space-y-3">
            @if($isLoading)
                <div class="text-center py-8">
                    <x-filament::loading-indicator class="h-8 w-8 mx-auto"/>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">Scanning for anomalies...</p>
                </div>
            @elseif($anomalies && count($anomalies) > 0)
                <div class="mb-4 p-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800">
                    <p class="text-sm text-warning-800 dark:text-warning-200">
                        <strong>{{ count($anomalies) }} anomal{{ count($anomalies) === 1 ? 'y' : 'ies' }} detected</strong> in your business metrics. Review the details below and take action as needed.
                    </p>
                </div>

                @foreach($anomalies as $anomaly)
                    <div class="border rounded-lg p-4
                        @if($anomaly['severity'] === 'high')
                            bg-danger-50 dark:bg-danger-950 border-danger-200 dark:border-danger-800
                        @elseif($anomaly['severity'] === 'medium')
                            bg-warning-50 dark:bg-warning-950 border-warning-200 dark:border-warning-800
                        @else
                            bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800
                        @endif
                    ">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <x-filament::icon
                                    :icon="$this->getSeverityIcon($anomaly['severity'])"
                                    class="h-6 w-6
                                        @if($anomaly['severity'] === 'high')
                                            text-danger-500
                                        @elseif($anomaly['severity'] === 'medium')
                                            text-warning-500
                                        @else
                                            text-info-500
                                        @endif
                                    "
                                />
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-semibold
                                        @if($anomaly['severity'] === 'high')
                                            text-danger-900 dark:text-danger-100
                                        @elseif($anomaly['severity'] === 'medium')
                                            text-warning-900 dark:text-warning-100
                                        @else
                                            text-info-900 dark:text-info-100
                                        @endif
                                    ">
                                        {{ $anomaly['metric'] }}
                                    </h4>
                                    <x-filament::badge
                                        :color="$this->getSeverityColor($anomaly['severity'])"
                                        size="sm"
                                    >
                                        {{ ucfirst($anomaly['severity']) }}
                                    </x-filament::badge>
                                </div>

                                <p class="text-sm mb-2
                                    @if($anomaly['severity'] === 'high')
                                        text-danger-700 dark:text-danger-300
                                    @elseif($anomaly['severity'] === 'medium')
                                        text-warning-700 dark:text-warning-300
                                    @else
                                        text-info-700 dark:text-info-300
                                    @endif
                                ">
                                    {{ $anomaly['description'] }}
                                </p>

                                @if(isset($anomaly['recommendation']))
                                    <div class="mt-2 p-2 rounded
                                        @if($anomaly['severity'] === 'high')
                                            bg-danger-100 dark:bg-danger-900
                                        @elseif($anomaly['severity'] === 'medium')
                                            bg-warning-100 dark:bg-warning-900
                                        @else
                                            bg-info-100 dark:bg-info-900
                                        @endif
                                    ">
                                        <p class="text-xs font-medium
                                            @if($anomaly['severity'] === 'high')
                                                text-danger-800 dark:text-danger-200
                                            @elseif($anomaly['severity'] === 'medium')
                                                text-warning-800 dark:text-warning-200
                                            @else
                                                text-info-800 dark:text-info-200
                                            @endif
                                        ">
                                            <x-filament::icon
                                                icon="heroicon-m-light-bulb"
                                                class="h-3 w-3 inline mr-1"
                                            />
                                            Recommendation: {{ $anomaly['recommendation'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-12 w-12 mx-auto text-success-500"
                    />
                    <p class="mt-3 text-sm font-medium text-success-700 dark:text-success-300">
                        No anomalies detected
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Your business metrics are within normal ranges.
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
