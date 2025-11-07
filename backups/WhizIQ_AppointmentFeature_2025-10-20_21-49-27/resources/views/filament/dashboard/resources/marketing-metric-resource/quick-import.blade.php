<div class="space-y-6">
    @php
        $platforms = [
            'meta' => [
                'name' => 'Meta Ads Manager',
                'description' => 'Facebook & Instagram Ads',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'info',
            ],
            'google' => [
                'name' => 'Google Ads',
                'description' => 'Search, Display & YouTube Ads',
                'icon' => 'heroicon-o-magnifying-glass',
                'color' => 'success',
            ],
            'linkedin' => [
                'name' => 'LinkedIn Ads',
                'description' => 'Professional B2B Advertising',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'primary',
            ],
            'tiktok' => [
                'name' => 'TikTok Ads',
                'description' => 'Short-Form Video Advertising',
                'icon' => 'heroicon-o-play-circle',
                'color' => 'danger',
            ],
            'twitter' => [
                'name' => 'X (Twitter) Ads',
                'description' => 'Social Media Advertising',
                'icon' => 'heroicon-o-hashtag',
                'color' => 'gray',
            ],
            'pinterest' => [
                'name' => 'Pinterest Ads',
                'description' => 'Visual Discovery Platform',
                'icon' => 'heroicon-o-photo',
                'color' => 'warning',
            ],
        ];
    @endphp

    {{-- Header Section --}}
    <div class="text-center space-y-2 py-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
            Connect Your Advertising Platforms
        </h3>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            One-click integration with major advertising platforms. Connect once and automatically import your campaign metrics, performance data, and ROI analytics.
        </p>
    </div>

    {{-- Platform Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($platforms as $platformKey => $platform)
            @php
                // Map platform keys to connection platform values
                $connectionPlatform = $platformKey === 'meta' ? 'facebook' : $platformKey;
                $connection = $connections->get($connectionPlatform);
                $isConnected = $connection && $connection->is_active;
            @endphp

            <div class="relative group">
                {{-- Connected Badge --}}
                @if($isConnected)
                    <div class="absolute -top-2 -right-2 z-10">
                        <span class="flex items-center gap-1 px-3 py-1 rounded-full bg-green-500 text-white text-xs font-semibold shadow-lg">
                            <x-filament::icon icon="heroicon-m-check-circle" class="h-3 w-3" />
                            Connected
                        </span>
                    </div>
                @endif

                {{-- Card --}}
                <div class="border-2 rounded-xl p-6 bg-white dark:bg-gray-800 hover:shadow-xl transition-all duration-200 {{ $isConnected ? 'border-green-400 dark:border-green-600' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }} h-full flex flex-col">

                    {{-- Platform Icon & Name --}}
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-lg bg-gradient-to-br {{ $isConnected ? 'from-green-400 to-green-600' : 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800' }} flex items-center justify-center shadow-md">
                                <x-filament::icon
                                    :icon="$platform['icon']"
                                    class="h-7 w-7 {{ $isConnected ? 'text-white' : 'text-gray-600 dark:text-gray-300' }}"
                                />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {{ $platform['name'] }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $platform['description'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Connection Status & Last Sync --}}
                    @if($isConnected && $connection->last_synced_at)
                        <div class="mb-4 px-3 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-2 text-xs">
                                <x-filament::icon icon="heroicon-m-clock" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                                <span class="text-green-700 dark:text-green-300">
                                    Last synced {{ $connection->last_synced_at->diffForHumans() }}
                                </span>
                            </div>
                            @if($connection->account_name)
                                <div class="flex items-center gap-2 text-xs mt-1">
                                    <x-filament::icon icon="heroicon-m-building-office" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300 truncate">
                                        {{ $connection->account_name }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Sync Error Message --}}
                    @if($isConnected && $connection->sync_status === 'failed')
                        <div class="mb-4 px-3 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-start gap-2">
                                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                                <div class="text-xs text-red-700 dark:text-red-300">
                                    <strong>Last sync failed</strong>
                                    @if($connection->last_error)
                                        <p class="mt-0.5">{{ $connection->last_error }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="mt-auto space-y-2">
                        @if($isConnected)
                            {{-- Fetch Data Button --}}
                            <x-filament::button
                                wire:click="fetchFromPlatform('{{ $connectionPlatform }}')"
                                :color="$platform['color']"
                                class="w-full justify-center"
                                size="lg"
                            >
                                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-5 w-5 mr-2" />
                                Import Latest Data
                            </x-filament::button>

                            {{-- Disconnect Button --}}
                            <x-filament::button
                                wire:click="disconnectPlatform('{{ $connectionPlatform }}')"
                                color="gray"
                                class="w-full justify-center"
                                size="sm"
                                outlined
                            >
                                <x-filament::icon icon="heroicon-m-x-circle" class="h-4 w-4 mr-1" />
                                Disconnect
                            </x-filament::button>
                        @else
                            {{-- Connect Button --}}
                            @php
                                $connectMethods = [
                                    'facebook' => 'connectMeta',
                                    'google' => 'connectGoogle',
                                    'linkedin' => 'connectLinkedIn',
                                    'tiktok' => 'connectTikTok',
                                    'twitter' => 'connectTwitter',
                                    'pinterest' => 'connectPinterest',
                                ];
                            @endphp

                            <x-filament::button
                                wire:click="{{ $connectMethods[$connectionPlatform] ?? 'showComingSoon' }}"
                                :color="$platform['color']"
                                class="w-full justify-center"
                                size="lg"
                                :disabled="!isset($connectMethods[$connectionPlatform])"
                            >
                                <x-filament::icon icon="heroicon-m-link" class="h-5 w-5 mr-2" />
                                {{ isset($connectMethods[$connectionPlatform]) ? 'Connect ' . $platform['name'] : 'Coming Soon' }}
                            </x-filament::button>

                            <p class="text-xs text-center text-gray-500 dark:text-gray-400 px-2">
                                One-time setup to auto-import metrics
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Information Banner --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-950/30 p-6 border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <x-filament::icon
                    icon="heroicon-o-information-circle"
                    class="h-6 w-6 text-blue-500"
                />
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                    How It Works
                </h4>
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p><strong>Step 1:</strong> Click "Connect" on any platform and authorize access to your ad account</p>
                    <p><strong>Step 2:</strong> Click "Import Latest Data" to automatically fetch your campaign metrics</p>
                    <p><strong>Step 3:</strong> Your data is imported and ready for analysis in your dashboard</p>
                </div>
                <div class="mt-3 text-xs text-blue-600 dark:text-blue-400">
                    <strong>Note:</strong> You only need to connect once. After that, you can import data anytime with one click.
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Entry Option (Coming Soon) --}}
    {{-- Uncomment when manual entry page is needed
    <div class="text-center py-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Need to enter data manually? Contact support for assistance.
        </p>
    </div>
    --}}
</div>
