<x-filament-panels::page>
    @if(count($duplicateGroups) === 0)
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-8 text-center border border-green-200 dark:border-green-800">
            <div class="flex flex-col items-center gap-4">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-green-900 dark:text-green-100 mb-2">No Duplicates Found!</h3>
                    <p class="text-green-700 dark:text-green-300">Your contact database is clean. All contacts are unique.</p>
                </div>
            </div>
        </div>
    @else
        <div class="mb-6">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100">{{ count($duplicateGroups) }} Potential Duplicate Groups Found</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Review each group below and decide whether to merge or keep separate.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @foreach($duplicateGroups as $index => $group)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Duplicate Group #{{ $index + 1 }}</h4>
                    </div>

                    <div class="p-6">
                        {{-- Primary Contact --}}
                        <div class="mb-6">
                            <div class="flex items-center gap-2 mb-3">
                                <x-heroicon-s-star class="w-5 h-5 text-yellow-500" />
                                <h5 class="font-semibold text-gray-900 dark:text-white">Primary Contact</h5>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">{{ $group['primary']['name'] }}</p>
                                        @if($group['primary']['company'])
                                            <p class="text-sm text-blue-700 dark:text-blue-300">{{ $group['primary']['company'] }}</p>
                                        @endif
                                    </div>
                                    <div class="text-sm text-blue-800 dark:text-blue-200">
                                        @if($group['primary']['email'])
                                            <p><strong>Email:</strong> {{ $group['primary']['email'] }}</p>
                                        @endif
                                        @if($group['primary']['phone'])
                                            <p><strong>Phone:</strong> {{ $group['primary']['phone'] }}</p>
                                        @endif
                                        <p><strong>Deals:</strong> {{ $group['primary']['deals_count'] }}</p>
                                        <p><strong>LTV:</strong> ${{ number_format($group['primary']['lifetime_value'], 0) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button
                                        wire:click="viewContact({{ $group['primary']['id'] }})"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                                    >
                                        View Contact →
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Potential Duplicates --}}
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <x-heroicon-o-document-duplicate class="w-5 h-5 text-gray-500" />
                                <h5 class="font-semibold text-gray-900 dark:text-white">Potential Duplicates ({{ count($group['duplicates']) }})</h5>
                            </div>

                            <div class="space-y-3">
                                @foreach($group['duplicates'] as $duplicate)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $duplicate['name'] }}</p>
                                                        @if($duplicate['company'])
                                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $duplicate['company'] }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                                        @if($duplicate['email'])
                                                            <p><strong>Email:</strong> {{ $duplicate['email'] }}</p>
                                                        @endif
                                                        @if($duplicate['phone'])
                                                            <p><strong>Phone:</strong> {{ $duplicate['phone'] }}</p>
                                                        @endif
                                                        <p><strong>Deals:</strong> {{ $duplicate['deals_count'] }}</p>
                                                        <p><strong>LTV:</strong> ${{ number_format($duplicate['lifetime_value'], 0) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $duplicate['confidence'] >= 90 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                                    {{ $duplicate['confidence'] >= 70 && $duplicate['confidence'] < 90 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                                    {{ $duplicate['confidence'] < 70 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                                    {{ $duplicate['confidence'] }}% match
                                                </span>
                                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $duplicate['reason'] }}</span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    wire:click="viewContact({{ $duplicate['id'] }})"
                                                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                                                >
                                                    View
                                                </button>

                                                <button
                                                    wire:click="mergeContacts({{ $group['primary']['id'] }}, {{ $duplicate['id'] }})"
                                                    wire:confirm="Are you sure you want to merge these contacts? This will move all deals, interactions, and data to the primary contact. This action cannot be undone."
                                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition"
                                                >
                                                    Merge Into Primary
                                                </button>

                                                <button
                                                    wire:click="ignoreDuplicate({{ $duplicate['id'] }})"
                                                    class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-zinc-300 dark:hover:bg-gray-500 text-gray-700 dark:text-dark-500 text-sm rounded transition"
                                                >
                                                    Not a Duplicate
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div class="text-sm">
                    <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">How Duplicate Detection Works:</p>
                    <ul class="text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                        <li><strong>100% confidence:</strong> Exact email match</li>
                        <li><strong>95% confidence:</strong> Exact phone number match</li>
                        <li><strong>85-90% confidence:</strong> Very similar name and company</li>
                        <li><strong>Merging:</strong> Combines all deals, interactions, and data into one contact</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
