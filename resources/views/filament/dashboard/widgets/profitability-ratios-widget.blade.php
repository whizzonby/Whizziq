<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-chart-bar"
                    class="h-5 w-5 text-primary-500"
                />
                <span>Profitability Ratios (This Month)</span>
            </div>
        </x-slot>

        @php
            $ratios = $this->getRatios();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($ratios as $ratio)
                <div class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:shadow-lg transition-shadow duration-200">
                    <!-- Header -->
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-{{ $ratio['status']['color'] }}-100 dark:bg-{{ $ratio['status']['color'] }}-900/20">
                                    <x-filament::icon
                                        :icon="$ratio['icon']"
                                        class="h-6 w-6 text-{{ $ratio['status']['color'] }}-600 dark:text-{{ $ratio['status']['color'] }}-400"
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ $ratio['label'] }}
                                    </h4>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                                        {{ $ratio['value'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center gap-2 mb-3">
                            <x-filament::icon
                                :icon="$ratio['status']['icon']"
                                class="h-4 w-4 text-{{ $ratio['status']['color'] }}-600"
                            />
                            <x-filament::badge
                                :color="$ratio['status']['color']"
                                size="sm"
                            >
                                {{ $ratio['status']['label'] }}
                            </x-filament::badge>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            {{ $ratio['description'] }}
                        </p>

                        <!-- Benchmark -->
                        <div class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded px-2 py-1">
                            <x-filament::icon
                                icon="heroicon-m-flag"
                                class="h-3 w-3"
                            />
                            <span>{{ $ratio['benchmark'] }}</span>
                        </div>
                    </div>

                    <!-- Color Bar at Bottom -->
                    <div class="h-1 bg-gradient-to-r from-{{ $ratio['status']['color'] }}-400 to-{{ $ratio['status']['color'] }}-600"></div>
                </div>
            @endforeach
        </div>

        <!-- Information Box -->
        <div class="mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-3">
                <x-filament::icon
                    icon="heroicon-o-information-circle"
                    class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5"
                />
                <div class="text-sm">
                    <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Understanding Your Ratios</p>
                    <p class="text-blue-700 dark:text-blue-300">
                        <strong>Net Margin</strong> shows overall profitability after all expenses.
                        <strong>Operating Margin</strong> focuses on core business operations.
                        <strong>Expense Ratio</strong> indicates how much you're spending relative to revenue.
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
