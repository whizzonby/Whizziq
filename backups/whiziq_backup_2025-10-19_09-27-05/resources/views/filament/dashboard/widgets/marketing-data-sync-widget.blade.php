<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-arrow-path"
                    class="h-5 w-5 text-primary-500"
                />
                <span>Marketing Data Sync</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            @php
                $status = $this->getSyncStatus();
            @endphp

            @if($status['total'] > 0)
                <!-- Sync Status Overview -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400">Connected</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $status['total'] }}</p>
                    </div>
                    <div class="rounded-lg bg-success-50 dark:bg-success-950 p-3 text-center">
                        <p class="text-xs text-success-600 dark:text-success-400">Synced</p>
                        <p class="text-2xl font-bold text-success-700 dark:text-success-300">{{ $status['success'] }}</p>
                    </div>
                    <div class="rounded-lg bg-info-50 dark:bg-info-950 p-3 text-center">
                        <p class="text-xs text-info-600 dark:text-info-400">Syncing</p>
                        <p class="text-2xl font-bold text-info-700 dark:text-info-300">{{ $status['syncing'] }}</p>
                    </div>
                    <div class="rounded-lg bg-danger-50 dark:bg-danger-950 p-3 text-center">
                        <p class="text-xs text-danger-600 dark:text-danger-400">Failed</p>
                        <p class="text-2xl font-bold text-danger-700 dark:text-danger-300">{{ $status['failed'] }}</p>
                    </div>
                </div>

                <!-- Last Sync Info -->
                @if($status['last_sync'])
                    <div class="flex items-center justify-between p-3 rounded-lg bg-primary-50 dark:bg-primary-950 border border-primary-200 dark:border-primary-800">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-o-clock"
                                class="h-4 w-4 text-primary-500"
                            />
                            <span class="text-sm text-primary-900 dark:text-primary-100">
                                Last synced: {{ $status['last_sync']->diffForHumans() }}
                            </span>
                        </div>
                        <span class="text-xs text-primary-700 dark:text-primary-300">
                            {{ $status['last_sync']->format('M d, Y g:i A') }}
                        </span>
                    </div>
                @endif

                <!-- Connected Accounts -->
                <div class="space-y-2">
                    @foreach($status['connections'] as $connection)
                        <div class="flex items-center justify-between p-3 rounded border dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <x-filament::icon
                                    :icon="$connection->platform_icon"
                                    class="h-5 w-5 text-gray-600 dark:text-gray-400"
                                />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $connection->platform_name }}
                                    </p>
                                    @if($connection->last_synced_at)
                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                            {{ $connection->last_synced_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <x-filament::badge
                                :color="$connection->status_color"
                                size="sm"
                            >
                                {{ ucfirst($connection->sync_status) }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>

                <!-- Sync Button -->
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="syncAll"
                        wire:loading.attr="disabled"
                        color="primary"
                        class="flex-1"
                    >
                        <span wire:loading.remove wire:target="syncAll">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4 inline mr-1" />
                            Sync All Accounts Now
                        </span>
                        <span wire:loading wire:target="syncAll">
                            <x-filament::loading-indicator class="h-4 w-4 inline mr-1" />
                            Syncing...
                        </span>
                    </x-filament::button>

                    <x-filament::button
                        href="{{ route('filament.dashboard.pages.social-media-connections-page') }}"
                        color="gray"
                        outlined
                    >
                        Manage Connections
                    </x-filament::button>
                </div>

                <!-- Auto-Sync Info -->
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-information-circle" class="h-3 w-3 inline mr-1" />
                        Auto-sync runs daily at midnight. Last automatic sync: {{ $status['last_sync'] ? $status['last_sync']->format('M d, g:i A') : 'Never' }}
                    </p>
                </div>
            @else
                <!-- No Connections State -->
                <div class="text-center py-8">
                    <x-filament::icon
                        icon="heroicon-o-link-slash"
                        class="h-16 w-16 mx-auto text-gray-400 mb-4"
                    />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        No Social Media Accounts Connected
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 max-w-md mx-auto">
                        Connect your Facebook, Instagram, Google Ads, or LinkedIn accounts to automatically sync your marketing metrics.
                    </p>
                    <x-filament::button
                        href="{{ route('filament.dashboard.pages.social-media-connections-page') }}"
                        color="primary"
                    >
                        <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4 inline mr-1" />
                        Connect Your First Account
                    </x-filament::button>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
