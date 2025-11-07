<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-funnel"
                    class="h-5 w-5 text-primary-500"
                />
                <span>Conversion Funnel</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            @php
                $stages = $this->getFunnelStages();
                $funnelData = $this->getFunnelData();
            @endphp

            <!-- Overall Conversion Rate Banner -->
            <div class="rounded-lg bg-gradient-to-r from-primary-50 to-success-50 dark:from-primary-950 dark:to-success-950 p-4 border border-primary-200 dark:border-primary-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Conversion Rate</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">From Awareness to Conversion</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $funnelData['overall_conversion_rate'] }}%</p>
                    </div>
                </div>
            </div>

            <!-- Funnel Stages -->
            <div class="space-y-3">
                @foreach($stages as $index => $stage)
                    <div class="relative">
                        <!-- Stage Card -->
                        <div class="rounded-lg border p-4 transition-all hover:shadow-md
                            @if($stage['color'] === 'info')
                                bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800
                            @elseif($stage['color'] === 'primary')
                                bg-primary-50 dark:bg-primary-950 border-primary-200 dark:border-primary-800
                            @elseif($stage['color'] === 'success')
                                bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800
                            @elseif($stage['color'] === 'warning')
                                bg-warning-50 dark:bg-warning-950 border-warning-200 dark:border-warning-800
                            @endif
                        ">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <x-filament::icon
                                            :icon="$stage['icon']"
                                            class="h-6 w-6
                                                @if($stage['color'] === 'info') text-info-500
                                                @elseif($stage['color'] === 'primary') text-primary-500
                                                @elseif($stage['color'] === 'success') text-success-500
                                                @elseif($stage['color'] === 'warning') text-warning-500
                                                @endif
                                            "
                                        />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold
                                            @if($stage['color'] === 'info') text-info-900 dark:text-info-100
                                            @elseif($stage['color'] === 'primary') text-primary-900 dark:text-primary-100
                                            @elseif($stage['color'] === 'success') text-success-900 dark:text-success-100
                                            @elseif($stage['color'] === 'warning') text-warning-900 dark:text-warning-100
                                            @endif
                                        ">
                                            {{ $stage['name'] }}
                                        </h4>
                                        <p class="text-xs
                                            @if($stage['color'] === 'info') text-info-600 dark:text-info-400
                                            @elseif($stage['color'] === 'primary') text-primary-600 dark:text-primary-400
                                            @elseif($stage['color'] === 'success') text-success-600 dark:text-success-400
                                            @elseif($stage['color'] === 'warning') text-warning-600 dark:text-warning-400
                                            @endif
                                        ">
                                            @if($index > 0)
                                                {{ $stage['percentage'] }}% conversion from previous stage
                                            @else
                                                Total audience reached
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold
                                        @if($stage['color'] === 'info') text-info-600 dark:text-info-400
                                        @elseif($stage['color'] === 'primary') text-primary-600 dark:text-primary-400
                                        @elseif($stage['color'] === 'success') text-success-600 dark:text-success-400
                                        @elseif($stage['color'] === 'warning') text-warning-600 dark:text-warning-400
                                        @endif
                                    ">
                                        {{ number_format($stage['value']) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            @if($index > 0)
                                <div class="mt-3">
                                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full
                                            @if($stage['color'] === 'info') bg-info-500
                                            @elseif($stage['color'] === 'primary') bg-primary-500
                                            @elseif($stage['color'] === 'success') bg-success-500
                                            @elseif($stage['color'] === 'warning') bg-warning-500
                                            @endif
                                            transition-all duration-500"
                                            style="width: {{ min($stage['percentage'], 100) }}%">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Connector Arrow -->
                        @if($index < count($stages) - 1)
                            <div class="flex justify-center my-2">
                                <x-filament::icon
                                    icon="heroicon-m-arrow-down"
                                    class="h-5 w-5 text-gray-400"
                                />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">Lead Rate</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $funnelData['lead_conversion_rate'] }}%</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">Close Rate</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $funnelData['customer_conversion_rate'] }}%</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">Retention</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $funnelData['retention_rate'] }}%</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">Overall</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $funnelData['overall_conversion_rate'] }}%</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
