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

            <div class="relative overflow-hidden rounded-xl border-l-4 border-{{ $color }}-500
                @if($alert['type'] === 'success')
                    bg-gradient-to-r from-emerald-50 via-green-50 to-teal-50 dark:from-emerald-950/20 dark:via-green-950/20 dark:to-teal-950/20
                    shadow-lg shadow-emerald-100/50 dark:shadow-emerald-900/20
                @else
                    bg-{{ $color }}-50 dark:bg-{{ $color }}-950/30 shadow-sm
                @endif
                p-6 transition-all duration-300 hover:shadow-xl">
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        @if($alert['type'] === 'success')
                            <div class="relative">
                                <div class="absolute inset-0 bg-emerald-400/20 rounded-full animate-pulse"></div>
                                <div class="relative bg-gradient-to-br from-emerald-500 to-green-600 rounded-full p-2 shadow-lg">
                                    <x-filament::icon
                                        :icon="$alert['icon']"
                                        class="h-6 w-6 text-white"
                                    />
                                </div>
                            </div>
                        @else
                            <x-filament::icon
                                :icon="$alert['icon']"
                                class="h-6 w-6 text-{{ $color }}-600 dark:text-{{ $color }}-400"
                            />
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <h4 class="@if($alert['type'] === 'success') text-lg font-bold bg-gradient-to-r from-emerald-700 to-green-700 dark:from-emerald-300 dark:to-green-300 bg-clip-text text-transparent @else text-sm font-semibold text-{{ $color }}-900 dark:text-{{ $color }}-100 @endif">
                            {{ $alert['title'] }}
                        </h4>
                        <p class="@if($alert['type'] === 'success') mt-2 text-base text-emerald-800 dark:text-emerald-200 font-medium @else mt-1 text-sm text-{{ $color }}-700 dark:text-{{ $color }}-300 @endif">
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

                <!-- Decorative Elements for Success Alerts -->
                @if($alert['type'] === 'success')
                    <!-- Subtle background pattern -->
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-5">
                        <svg class="w-full h-full text-emerald-600" fill="currentColor" viewBox="0 0 100 100">
                            <circle cx="20" cy="20" r="2"/>
                            <circle cx="40" cy="20" r="2"/>
                            <circle cx="60" cy="20" r="2"/>
                            <circle cx="80" cy="20" r="2"/>
                            <circle cx="20" cy="40" r="2"/>
                            <circle cx="40" cy="40" r="2"/>
                            <circle cx="60" cy="40" r="2"/>
                            <circle cx="80" cy="40" r="2"/>
                            <circle cx="20" cy="60" r="2"/>
                            <circle cx="40" cy="60" r="2"/>
                            <circle cx="60" cy="60" r="2"/>
                            <circle cx="80" cy="60" r="2"/>
                            <circle cx="20" cy="80" r="2"/>
                            <circle cx="40" cy="80" r="2"/>
                            <circle cx="60" cy="80" r="2"/>
                            <circle cx="80" cy="80" r="2"/>
                        </svg>
                    </div>
                    
                    <!-- Success badge -->
                    <div class="absolute top-4 right-4">
                        <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                            ✓ HEALTHY
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
