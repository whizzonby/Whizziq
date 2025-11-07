<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-currency-dollar"
                    class="h-5 w-5 text-warning-500"
                />
                <span>Customer Lifetime Value vs Acquisition Cost</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            @php
                $data = $this->getCLVCACData();
                $recommendation = $this->getRecommendation();
            @endphp

            <!-- Main Ratio Display -->
            <div class="rounded-lg p-6 text-center
                @if($data['health_color'] === 'success')
                    bg-success-50 dark:bg-success-950 border-2 border-success-300 dark:border-success-800
                @elseif($data['health_color'] === 'primary')
                    bg-primary-50 dark:bg-primary-950 border-2 border-primary-300 dark:border-primary-800
                @elseif($data['health_color'] === 'warning')
                    bg-warning-50 dark:bg-warning-950 border-2 border-warning-300 dark:border-warning-800
                @elseif($data['health_color'] === 'gray')
                    bg-gray-50 dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-800
                @else
                    bg-danger-50 dark:bg-danger-950 border-2 border-danger-300 dark:border-danger-800
                @endif
            ">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">CLV:CAC Ratio</p>
                <p class="text-5xl font-bold
                    @if($data['health_color'] === 'success') text-success-600 dark:text-success-400
                    @elseif($data['health_color'] === 'primary') text-primary-600 dark:text-primary-400
                    @elseif($data['health_color'] === 'warning') text-warning-600 dark:text-warning-400
                    @elseif($data['health_color'] === 'gray') text-gray-600 dark:text-gray-400
                    @else text-danger-600 dark:text-danger-400
                    @endif
                ">
                    {{ number_format($data['ratio'], 2) }}:1
                </p>
                <x-filament::badge
                    :color="$data['health_color']"
                    size="lg"
                    class="mt-3"
                >
                    {{ $data['health'] }}
                </x-filament::badge>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Industry benchmark: 3:1 or higher
                </p>
            </div>

            <!-- AI Recommendation -->
            <div class="rounded-lg bg-info-50 dark:bg-info-950 p-4 border border-info-200 dark:border-info-800">
                <div class="flex items-start gap-3">
                    <x-filament::icon
                        icon="heroicon-o-light-bulb"
                        class="h-5 w-5 text-info-500 flex-shrink-0 mt-0.5"
                    />
                    <div>
                        <p class="text-sm font-medium text-info-900 dark:text-info-100 mb-1">
                            Recommendation
                        </p>
                        <p class="text-xs text-info-700 dark:text-info-300">
                            {{ $recommendation }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- CLV vs CAC Comparison -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- CLV Card -->
                <div class="rounded-lg bg-success-50 dark:bg-success-950 p-4 border border-success-200 dark:border-success-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-o-arrow-trending-up"
                                class="h-5 w-5 text-success-500"
                            />
                            <h4 class="text-sm font-semibold text-success-900 dark:text-success-100">
                                Customer Lifetime Value
                            </h4>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-success-600 dark:text-success-400">
                        ${{ number_format($data['avg_clv'], 2) }}
                    </p>
                    <p class="text-xs text-success-600 dark:text-success-400 mt-1">
                        Average per customer
                    </p>
                    <div class="mt-3 pt-3 border-t border-success-200 dark:border-success-800">
                        <p class="text-xs text-success-700 dark:text-success-300">
                            Total CLV: ${{ number_format($data['total_clv'], 2) }}
                        </p>
                    </div>
                </div>

                <!-- CAC Card -->
                <div class="rounded-lg bg-warning-50 dark:bg-warning-950 p-4 border border-warning-200 dark:border-warning-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-o-banknotes"
                                class="h-5 w-5 text-warning-500"
                            />
                            <h4 class="text-sm font-semibold text-warning-900 dark:text-warning-100">
                                Customer Acquisition Cost
                            </h4>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-warning-600 dark:text-warning-400">
                        ${{ number_format($data['avg_cac'], 2) }}
                    </p>
                    <p class="text-xs text-warning-600 dark:text-warning-400 mt-1">
                        Average per customer
                    </p>
                    <div class="mt-3 pt-3 border-t border-warning-200 dark:border-warning-800">
                        <p class="text-xs text-warning-700 dark:text-warning-300">
                            Total CAC: ${{ number_format($data['total_cac'], 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Visual Comparison Bar -->
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Value Distribution</p>
                <div class="flex h-8 rounded-lg overflow-hidden">
                    <div class="bg-success-500 flex items-center justify-center text-white text-xs font-semibold"
                         style="width: {{ $this->getCLVPercentage() }}%">
                        @if($this->getCLVPercentage() > 15)
                            CLV {{ $this->getCLVPercentage() }}%
                        @endif
                    </div>
                    <div class="bg-warning-500 flex items-center justify-center text-white text-xs font-semibold"
                         style="width: {{ $this->getCACPercentage() }}%">
                        @if($this->getCACPercentage() > 15)
                            CAC {{ $this->getCACPercentage() }}%
                        @endif
                    </div>
                </div>
            </div>

            <!-- Channel Breakdown -->
            @if(!empty($data['channels']))
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Channel Breakdown</h4>
                    <div class="space-y-2">
                        @foreach($data['channels'] as $channel)
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $channel['name'] }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        CLV: ${{ number_format($channel['clv'], 2) }} | CAC: ${{ number_format($channel['cac'], 2) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($channel['ratio'], 2) }}:1</p>
                                    <x-filament::badge
                                        :color="match($channel['health']) {
                                            'excellent' => 'success',
                                            'good' => 'primary',
                                            'acceptable' => 'warning',
                                            default => 'danger'
                                        }"
                                        size="xs"
                                    >
                                        {{ ucfirst($channel['health']) }}
                                    </x-filament::badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
