<x-filament-panels::page>
    @php
        $platforms = [
            'quickbooks' => [
                'name' => 'QuickBooks',
                'description' => 'Accounting & Financial Management',
                'icon' => 'heroicon-o-calculator',
                'color' => 'success',
                'type' => 'oauth',
            ],
            'xero' => [
                'name' => 'Xero',
                'description' => 'Cloud Accounting Software',
                'icon' => 'heroicon-o-cloud',
                'color' => 'info',
                'type' => 'oauth',
            ],
            'excel' => [
                'name' => 'Excel / CSV',
                'description' => 'Upload Spreadsheet Files',
                'icon' => 'heroicon-o-document-text',
                'color' => 'primary',
                'type' => 'upload',
            ],
            'stripe' => [
                'name' => 'Stripe',
                'description' => 'Payment Processing Platform',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'warning',
                'type' => 'oauth',
            ],
            'oracle' => [
                'name' => 'Oracle Financials',
                'description' => 'Enterprise Resource Planning',
                'icon' => 'heroicon-o-building-office-2',
                'color' => 'danger',
                'type' => 'enterprise',
            ],
            'sap' => [
                'name' => 'SAP',
                'description' => 'Enterprise Business Software',
                'icon' => 'heroicon-o-server-stack',
                'color' => 'gray',
                'type' => 'enterprise',
            ],
        ];

        $connections = $this->getConnections();
    @endphp

    {{-- Header Section --}}
    <div class="text-center space-y-3 py-6 mb-8">
        <div class="flex items-center justify-center gap-3">
            <x-filament::icon
                icon="heroicon-o-banknotes"
                class="h-12 w-12 text-primary-500"
            />
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                Connect Your Financial Platforms
            </h2>
        </div>
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            One-click integration with major accounting and financial platforms. Import your revenue, expenses, and cash flow data automatically.
        </p>
    </div>

    {{-- Platform Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($platforms as $platformKey => $platform)
            @php
                $connection = $connections->get($platformKey);
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

                {{-- Enterprise Badge --}}
                @if($platform['type'] === 'enterprise')
                    <div class="absolute -top-2 -right-2 z-10">
                        <span class="px-3 py-1 rounded-full bg-purple-500 text-white text-xs font-semibold shadow-lg">
                            Enterprise
                        </span>
                    </div>
                @endif

                {{-- Card --}}
                <div class="border-2 rounded-xl p-6 bg-white dark:bg-gray-800 hover:shadow-xl transition-all duration-200 {{ $isConnected ? 'border-green-400 dark:border-green-600' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }} h-full flex flex-col">

                    {{-- Platform Icon & Name --}}
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br {{ $isConnected ? 'from-green-400 to-green-600' : 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800' }} flex items-center justify-center shadow-md">
                                <x-filament::icon
                                    :icon="$platform['icon']"
                                    class="h-8 w-8 {{ $isConnected ? 'text-white' : 'text-gray-600 dark:text-gray-300' }}"
                                />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
                                {{ $platform['name'] }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $platform['description'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Connection Status --}}
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

                    {{-- Action Buttons --}}
                    <div class="mt-auto space-y-2">
                        @if($isConnected)
                            {{-- Import Button --}}
                            <x-filament::button
                                wire:click="importFromPlatform('{{ $platformKey }}')"
                                :color="$platform['color']"
                                class="w-full justify-center"
                                size="lg"
                            >
                                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-5 w-5 mr-2" />
                                Import Data Now
                            </x-filament::button>

                            {{-- Disconnect Button --}}
                            <x-filament::button
                                wire:click="disconnectPlatform('{{ $platformKey }}')"
                                color="gray"
                                class="w-full justify-center"
                                size="sm"
                                outlined
                            >
                                <x-filament::icon icon="heroicon-m-x-circle" class="h-4 w-4 mr-1" />
                                Disconnect
                            </x-filament::button>
                        @else
                            {{-- Connect/Upload Buttons --}}
                            @if($platform['type'] === 'oauth')
                                @php
                                    $connectMethods = [
                                        'quickbooks' => 'connectQuickBooks',
                                        'xero' => 'connectXero',
                                        'stripe' => 'connectStripe',
                                    ];
                                @endphp

                                <x-filament::button
                                    wire:click="{{ $connectMethods[$platformKey] ?? 'showComingSoon' }}"
                                    :color="$platform['color']"
                                    class="w-full justify-center"
                                    size="lg"
                                >
                                    <x-filament::icon icon="heroicon-m-link" class="h-5 w-5 mr-2" />
                                    Connect {{ $platform['name'] }}
                                </x-filament::button>

                            @elseif($platform['type'] === 'upload')
                                <x-filament::button
                                    href="#"
                                    :color="$platform['color']"
                                    class="w-full justify-center"
                                    size="lg"
                                    x-on:click.prevent="$dispatch('open-modal', { id: 'upload-excel-modal' })"
                                >
                                    <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-5 w-5 mr-2" />
                                    Upload File
                                </x-filament::button>

                            @elseif($platform['type'] === 'enterprise')
                                <x-filament::button
                                    wire:click="showEnterprise('{{ $platform['name'] }}')"
                                    :color="$platform['color']"
                                    class="w-full justify-center"
                                    size="lg"
                                    outlined
                                >
                                    <x-filament::icon icon="heroicon-m-envelope" class="h-5 w-5 mr-2" />
                                    Contact for {{ $platform['name'] }}
                                </x-filament::button>
                            @endif

                            <p class="text-xs text-center text-gray-500 dark:text-gray-400 px-2">
                                @if($platform['type'] === 'enterprise')
                                    Enterprise integration available
                                @else
                                    One-time setup to auto-import
                                @endif
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
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    How Financial Import Works
                </h4>
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p><strong>Step 1:</strong> Click "Connect" on any platform and authorize access to your financial account</p>
                    <p><strong>Step 2:</strong> Click "Import Data Now" to automatically fetch your revenue and expense records</p>
                    <p><strong>Step 3:</strong> Your financial data is imported and instantly reflected in your dashboard analytics</p>
                </div>
                <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <p class="text-xs text-blue-800 dark:text-blue-200">
                        <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4 inline mr-1" />
                        <strong>Your data is secure:</strong> All connections are encrypted, and we only request read-only access to your financial data.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Excel/CSV Upload Modal --}}
    <x-filament::modal id="upload-excel-modal" width="2xl">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-document-arrow-up" class="h-6 w-6 text-primary-500" />
                <span>Upload Excel/CSV File</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Import your financial data from Excel or CSV files. We support revenue, expenses, and cash flow data.
        </x-slot>

        <form wire:submit="uploadFinancialFile">
            <div class="space-y-6">
                {{-- File Upload Field --}}
                <div>
                    {{ $this->form }}
                </div>

                {{-- File Format Information --}}
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800">
                    <h5 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                        Supported File Formats
                    </h5>
                    <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1 ml-6 list-disc">
                        <li><strong>Excel:</strong> .xlsx, .xls files</li>
                        <li><strong>CSV:</strong> .csv files with comma or semicolon delimiters</li>
                        <li><strong>Max file size:</strong> 10MB</li>
                    </ul>
                </div>

                {{-- Expected Columns Guide --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-900/30 p-4 border border-gray-200 dark:border-gray-700">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-table-cells" class="h-4 w-4" />
                        Expected Column Structure
                    </h5>
                    <div class="text-xs text-gray-700 dark:text-gray-300 space-y-2">
                        <p class="font-medium">Your file should include these columns:</p>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-check" class="h-3 w-3 text-green-600" />
                                <span>Date</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-check" class="h-3 w-3 text-green-600" />
                                <span>Description</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-check" class="h-3 w-3 text-green-600" />
                                <span>Amount</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-check" class="h-3 w-3 text-green-600" />
                                <span>Type (Revenue/Expense)</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-minus" class="h-3 w-3 text-gray-400" />
                                <span>Category (optional)</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Download Template Link --}}
                <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-950/30 rounded-lg border border-primary-200 dark:border-primary-800">
                    <div class="flex items-center gap-2 text-sm text-primary-700 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
                        <span>Need a template?</span>
                    </div>
                    <x-filament::button
                        href="{{ route('filament.dashboard.resources.finances.download-template') }}"
                        tag="a"
                        size="xs"
                        color="primary"
                        outlined
                    >
                        Download Sample CSV
                    </x-filament::button>
                </div>
            </div>

            {{-- Modal Actions --}}
            <x-slot name="footerActions">
                <x-filament::button type="submit" color="primary">
                    <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-5 w-5 mr-2" />
                    Upload & Import
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="close"
                >
                    Cancel
                </x-filament::button>
            </x-slot>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
