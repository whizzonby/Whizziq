<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Info Banner -->
        <div class="rounded-lg bg-info-50 dark:bg-info-950 p-4 border border-info-200 dark:border-info-800">
            <div class="flex items-start gap-3">
                <x-filament::icon
                    icon="heroicon-o-information-circle"
                    class="h-5 w-5 text-info-500 flex-shrink-0 mt-0.5"
                />
                <div>
                    <p class="text-sm font-medium text-info-900 dark:text-info-100">
                        Automate Your Marketing Data Collection
                    </p>
                    <p class="text-xs text-info-700 dark:text-info-300 mt-1">
                        Connect your social media and advertising accounts to automatically sync metrics like reach, engagement, conversions, and ROI. No more manual data entry!
                    </p>
                </div>
            </div>
        </div>

        <!-- Connected Accounts -->
        @php
            $connections = $this->getConnections();
        @endphp

        @if($connections->count() > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Connected Accounts
                </x-slot>

                <div class="space-y-3">
                    @foreach($connections as $connection)
                        <div class="flex items-center justify-between p-4 rounded-lg border
                            @if($connection->is_active)
                                bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800
                            @else
                                bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700
                            @endif
                        ">
                            <div class="flex items-center gap-3">
                                <x-filament::icon
                                    :icon="$connection->platform_icon"
                                    class="h-8 w-8
                                        @if($connection->is_active) text-success-500
                                        @else text-gray-400
                                        @endif
                                    "
                                />
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $connection->platform_name }}
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $connection->account_name ?? 'Account ID: ' . $connection->account_id }}
                                    </p>
                                    @if($connection->last_synced_at)
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            Last synced: {{ $connection->last_synced_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Status Badge -->
                                <x-filament::badge
                                    :color="$connection->status_color"
                                    size="sm"
                                >
                                    {{ ucfirst($connection->sync_status) }}
                                </x-filament::badge>

                                <!-- Actions -->
                                <x-filament::button
                                    wire:click="syncConnection({{ $connection->id }})"
                                    size="sm"
                                    color="primary"
                                    outlined
                                >
                                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                                    Sync
                                </x-filament::button>

                                <x-filament::button
                                    wire:click="toggleConnection({{ $connection->id }})"
                                    size="sm"
                                    :color="$connection->is_active ? 'warning' : 'success'"
                                    outlined
                                >
                                    {{ $connection->is_active ? 'Pause' : 'Activate' }}
                                </x-filament::button>

                                <x-filament::button
                                    wire:click="deleteConnection({{ $connection->id }})"
                                    size="sm"
                                    color="danger"
                                    outlined
                                >
                                    <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                                </x-filament::button>
                            </div>
                        </div>

                        @if($connection->sync_error)
                            <div class="ml-11 p-3 rounded bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800">
                                <p class="text-xs text-danger-700 dark:text-danger-300">
                                    <strong>Error:</strong> {{ $connection->sync_error }}
                                </p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        <!-- Available Connections -->
        <x-filament::section>
            <x-slot name="heading">
                Connect New Account
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Facebook -->
                <div class="border rounded-lg p-4 bg-white dark:bg-gray-800 hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-3">
                        <x-filament::icon
                            icon="heroicon-o-chat-bubble-left-right"
                            class="h-8 w-8 text-blue-600"
                        />
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Facebook & Instagram
                        </h4>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                        Connect your Facebook Pages and Instagram Business accounts to track engagement, reach, and post performance.
                    </p>
                    <x-filament::button
                        wire:click="connectFacebook"
                        color="primary"
                        class="w-full"
                    >
                        Connect Facebook
                    </x-filament::button>
                </div>

                <!-- Google Ads -->
                <div class="border rounded-lg p-4 bg-white dark:bg-gray-800 hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-3">
                        <x-filament::icon
                            icon="heroicon-o-magnifying-glass"
                            class="h-8 w-8 text-green-600"
                        />
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Google Ads
                        </h4>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                        Sync your Google Ads campaigns to automatically track clicks, conversions, ad spend, and ROI.
                    </p>
                    <x-filament::button
                        wire:click="connectGoogle"
                        color="success"
                        class="w-full"
                    >
                        Connect Google Ads
                    </x-filament::button>
                </div>

                <!-- LinkedIn -->
                <div class="border rounded-lg p-4 bg-white dark:bg-gray-800 hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-3">
                        <x-filament::icon
                            icon="heroicon-o-briefcase"
                            class="h-8 w-8 text-blue-700"
                        />
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            LinkedIn Ads
                        </h4>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                        Connect LinkedIn Campaign Manager to track B2B ad performance, leads, and professional engagement.
                    </p>
                    <x-filament::button
                        wire:click="connectLinkedIn"
                        color="info"
                        class="w-full"
                    >
                        Connect LinkedIn
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <!-- Setup Instructions -->
        <x-filament::section>
            <x-slot name="heading">
                Setup Instructions
            </x-slot>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <h4>Before Connecting:</h4>
                <ol>
                    <li>Make sure you have admin access to your social media/ad accounts</li>
                    <li>Configure API credentials in your <code>.env</code> file</li>
                    <li>For each platform, you'll need:
                        <ul>
                            <li><strong>Facebook/Instagram:</strong> App ID and App Secret from Facebook Developers</li>
                            <li><strong>Google Ads:</strong> Client ID, Client Secret, and Developer Token</li>
                            <li><strong>LinkedIn:</strong> Client ID and Client Secret from LinkedIn Developers</li>
                        </ul>
                    </li>
                </ol>

                <h4>How Auto-Sync Works:</h4>
                <ul>
                    <li>Data syncs automatically every 24 hours</li>
                    <li>Click "Sync" button to manually fetch latest data</li>
                    <li>Metrics appear in your Analytics Dashboard</li>
                    <li>All credentials are encrypted for security</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
