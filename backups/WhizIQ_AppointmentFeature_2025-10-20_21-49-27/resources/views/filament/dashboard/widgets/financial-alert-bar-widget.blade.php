<x-filament-widgets::widget>
    @php
        $alerts = $this->getAlerts();
        $alertColors = [
            'success' => 'green',
            'info' => 'blue',
            'warning' => 'yellow',
            'danger' => 'red',
        ];
    @endphp

    <div class="space-y-3">
        @foreach($alerts as $alert)
            @php
                $color = $alertColors[$alert['type']] ?? 'gray';
            @endphp

            <div class="relative overflow-hidden rounded-lg border-l-4 border-{{ $color }}-500 bg-{{ $color }}-50 dark:bg-{{ $color }}-950/30 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <x-filament::icon
                            :icon="$alert['icon']"
                            class="h-6 w-6 text-{{ $color }}-600 dark:text-{{ $color }}-400"
                        />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-{{ $color }}-900 dark:text-{{ $color }}-100">
                            {{ $alert['title'] }}
                        </h4>
                        <p class="mt-1 text-sm text-{{ $color }}-700 dark:text-{{ $color }}-300">
                            {{ $alert['message'] }}
                        </p>

                        @if($alert['action'])
                            <button
                                class="mt-2 text-xs font-medium text-{{ $color }}-800 dark:text-{{ $color }}-200 hover:text-{{ $color }}-900 dark:hover:text-{{ $color }}-100 underline"
                            >
                                {{ $alert['action'] }} →
                            </button>
                        @endif
                    </div>

                    <!-- Dismiss Button (for non-success alerts) -->
                    @if($alert['type'] !== 'success')
                        <div class="flex-shrink-0">
                            <button
                                type="button"
                                class="rounded p-1.5 hover:bg-{{ $color }}-100 dark:hover:bg-{{ $color }}-900/50 transition-colors"
                                onclick="this.closest('.border-{{ $color }}-500').remove()"
                            >
                                <x-filament::icon
                                    icon="heroicon-m-x-mark"
                                    class="h-4 w-4 text-{{ $color }}-600 dark:text-{{ $color }}-400"
                                />
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Animated Pulse Effect for Critical Alerts -->
                @if($alert['type'] === 'danger')
                    <div class="absolute top-0 right-0 -mr-1 -mt-1">
                        <span class="flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-{{ $color }}-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-{{ $color }}-500"></span>
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
