<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-light-bulb"
                        class="h-5 w-5 text-warning-500"
                    />
                    <span>Actionable Insights</span>
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

        <div class="space-y-3">
            @if($isLoading)
                <div class="text-center py-8">
                    <x-filament::loading-indicator class="h-8 w-8 mx-auto"/>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">Generating AI insights...</p>
                </div>
            @elseif($insights && count($insights) > 0)
                @foreach($insights as $insight)
                    <div class="flex items-start gap-3 p-4 rounded-lg border
                        @if($insight['type'] === 'warning')
                            bg-warning-50 dark:bg-warning-950 border-warning-200 dark:border-warning-800
                        @elseif($insight['type'] === 'success')
                            bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800
                        @elseif($insight['type'] === 'info')
                            bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800
                        @else
                            bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700
                        @endif
                    ">
                        <x-filament::icon
                            :icon="$insight['icon']"
                            class="h-5 w-5 flex-shrink-0
                                @if($insight['type'] === 'warning')
                                    text-warning-500
                                @elseif($insight['type'] === 'success')
                                    text-success-500
                                @elseif($insight['type'] === 'info')
                                    text-info-500
                                @else
                                    text-gray-500
                                @endif
                            "
                        />
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold
                                @if($insight['type'] === 'warning')
                                    text-warning-900 dark:text-warning-100
                                @elseif($insight['type'] === 'success')
                                    text-success-900 dark:text-success-100
                                @elseif($insight['type'] === 'info')
                                    text-info-900 dark:text-info-100
                                @else
                                    text-gray-900 dark:text-gray-100
                                @endif
                            ">
                                {{ $insight['title'] }}
                            </h4>
                            <p class="mt-1 text-sm
                                @if($insight['type'] === 'warning')
                                    text-warning-700 dark:text-warning-300
                                @elseif($insight['type'] === 'success')
                                    text-success-700 dark:text-success-300
                                @elseif($insight['type'] === 'info')
                                    text-info-700 dark:text-info-300
                                @else
                                    text-gray-700 dark:text-gray-300
                                @endif
                            ">
                                {{ trim($insight['description']) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-circle"
                        class="h-12 w-12 mx-auto text-gray-400"
                    />
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">No insights available at the moment.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
