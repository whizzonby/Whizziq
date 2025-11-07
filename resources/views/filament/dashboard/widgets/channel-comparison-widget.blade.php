<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-chart-bar-square"
                    class="h-5 w-5 text-success-500"
                />
                <span>Channel Performance Comparison</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            @php
                $channelData = $this->getChannelData();
                $bestChannel = $this->getBestPerformingChannel();
                $lowestCPCChannel = $this->getLowestCPCChannel();
            @endphp

            <!-- Insights Banner -->
            @if($bestChannel)
                <div class="rounded-lg bg-success-50 dark:bg-success-950 p-4 border border-success-200 dark:border-success-800">
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            icon="heroicon-o-light-bulb"
                            class="h-5 w-5 text-success-500 flex-shrink-0 mt-0.5"
                        />
                        <div>
                            <p class="text-sm font-medium text-success-900 dark:text-success-100">
                                Best Performing: {{ $bestChannel }}
                            </p>
                            <p class="text-xs text-success-700 dark:text-success-300 mt-1">
                                @if($lowestCPCChannel && $lowestCPCChannel !== $bestChannel)
                                    {{ $lowestCPCChannel }} has the lowest cost per click. Consider testing budget reallocation for optimal performance.
                                @else
                                    Leading in both ROI and efficiency metrics.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Channel Comparison Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($channelData as $channel => $data)
                    @php
                        $color = $this->getChannelColor($channel);
                        $icon = $this->getChannelIcon($channel);
                    @endphp
                    <div class="rounded-lg border p-4
                        @if($color === 'primary')
                            bg-primary-50 dark:bg-primary-950 border-primary-200 dark:border-primary-800
                        @elseif($color === 'success')
                            bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800
                        @elseif($color === 'info')
                            bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800
                        @endif
                    ">
                        <!-- Channel Header -->
                        <div class="flex items-center gap-2 mb-3">
                            <x-filament::icon
                                :icon="$icon"
                                class="h-6 w-6
                                    @if($color === 'primary') text-primary-500
                                    @elseif($color === 'success') text-success-500
                                    @elseif($color === 'info') text-info-500
                                    @endif
                                "
                            />
                            <h3 class="text-lg font-bold
                                @if($color === 'primary') text-primary-900 dark:text-primary-100
                                @elseif($color === 'success') text-success-900 dark:text-success-100
                                @elseif($color === 'info') text-info-900 dark:text-info-100
                                @endif
                            ">
                                {{ $data['name'] }}
                            </h3>
                        </div>

                        <!-- Metrics Grid -->
                        <div class="space-y-2">
                            <!-- Conversions -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Conversions</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($data['conversions']) }}</span>
                            </div>

                            <!-- Cost per Conversion -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Cost/Conversion</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($data['cost_per_conversion'], 2) }}</span>
                            </div>

                            <!-- Reach -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Reach</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($data['reach']) }}</span>
                            </div>

                            <!-- Ad Spend -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Ad Spend</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($data['ad_spend'], 2) }}</span>
                            </div>

                            <!-- ROI -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">ROI</span>
                                <span class="text-sm font-semibold
                                    @if($data['roi'] >= 100) text-success-600 dark:text-success-400
                                    @elseif($data['roi'] >= 50) text-warning-600 dark:text-warning-400
                                    @else text-danger-600 dark:text-danger-400
                                    @endif
                                ">
                                    {{ number_format($data['roi'], 1) }}%
                                </span>
                            </div>

                            <!-- Conversion Rate -->
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Conv. Rate</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($data['conversion_rate'], 2) }}%</span>
                            </div>

                            <!-- CPC -->
                            <div class="flex justify-between items-center py-2">
                                <span class="text-xs text-gray-600 dark:text-gray-400">CPC</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($data['cpc'], 2) }}</span>
                            </div>
                        </div>

                        <!-- Winner Badge -->
                        @if($data['name'] === $bestChannel)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <x-filament::badge color="success" size="sm">
                                    <x-filament::icon icon="heroicon-m-star" class="h-3 w-3 inline mr-1" />
                                    Best ROI
                                </x-filament::badge>
                            </div>
                        @endif
                        @if($data['name'] === $lowestCPCChannel && $lowestCPCChannel !== $bestChannel)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <x-filament::badge color="info" size="sm">
                                    <x-filament::icon icon="heroicon-m-currency-dollar" class="h-3 w-3 inline mr-1" />
                                    Lowest CPC
                                </x-filament::badge>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Comparison Summary -->
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 mt-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Comparison</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($channelData as $channel => $data)
                        <div class="text-center">
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $data['name'] }} Clicks</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($data['clicks']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
