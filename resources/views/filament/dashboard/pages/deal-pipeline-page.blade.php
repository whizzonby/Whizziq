<x-filament-panels::page>
    {{-- Stats Overview --}}
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Open Deals</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['open_deals_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-briefcase class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                ${{ number_format($stats['open_deals_value'], 0) }} total value
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Weighted Value</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['open_weighted_value'], 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-currency-dollar class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Expected revenue
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Average Deal</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['average_deal_size'], 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-chart-bar class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Per deal size
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Win Rate</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['win_rate'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-trophy class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $stats['won_deals_count'] }} deals won
            </p>
        </div>
    </div>

    {{-- Pipeline Kanban Board --}}
    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 min-h-[600px]">
            @foreach($stages as $stageKey => $stageInfo)
                <div class="flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow">
                    {{-- Stage Header --}}
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $stageInfo['label'] }}</h3>
                            <span class="text-xs px-2 py-1 rounded-full bg-{{ $stageInfo['color'] }}-100 dark:bg-{{ $stageInfo['color'] }}-900/30 text-{{ $stageInfo['color'] }}-600 dark:text-{{ $stageInfo['color'] }}-400">
                                {{ $stageStats[$stageKey]['count'] }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <div>${{ number_format($stageStats[$stageKey]['total_value'], 0) }}</div>
                            @if($stageKey !== 'won' && $stageKey !== 'lost')
                                <div class="text-green-600 dark:text-green-400">
                                    ~${{ number_format($stageStats[$stageKey]['weighted_value'], 0) }}
                                </div>
                            @endif
                        </div>
                        <button
                            wire:click="createDeal('{{ $stageKey }}')"
                            class="mt-2 w-full text-xs py-1 px-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors text-gray-700 dark:text-gray-300"
                        >
                            + Add Deal
                        </button>
                    </div>

                    {{-- Stage Deals --}}
                    <div
                        class="flex-1 p-2 space-y-2 overflow-y-auto deal-stage"
                        data-stage="{{ $stageKey }}"
                        style="max-height: 500px;"
                    >
                        @forelse($deals[$stageKey] ?? [] as $deal)
                            <div
                                class="deal-card bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600 hover:shadow-lg transition-all cursor-move"
                                data-deal-id="{{ $deal['id'] }}"
                                draggable="true"
                            >
                                {{-- Deal Header --}}
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-medium text-sm text-gray-900 dark:text-white line-clamp-2">
                                        {{ $deal['title'] }}
                                    </h4>
                                    @if($deal['priority'] === 'high')
                                        <span class="ml-1 flex-shrink-0">
                                            <x-heroicon-s-exclamation-circle class="w-4 h-4 text-red-500" />
                                        </span>
                                    @endif
                                </div>

                                {{-- Contact --}}
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    <x-heroicon-o-user class="w-3 h-3 mr-1" />
                                    {{ $deal['contact_name'] }}
                                </div>

                                {{-- Value --}}
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        ${{ number_format($deal['value'], 0) }}
                                    </span>
                                    @if($stageKey !== 'won' && $stageKey !== 'lost')
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $deal['probability'] }}%
                                        </span>
                                    @endif
                                </div>

                                {{-- Expected Close Date --}}
                                @if($deal['expected_close_date'])
                                    <div class="flex items-center text-xs mb-2 {{ $deal['is_overdue'] ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        <x-heroicon-o-calendar class="w-3 h-3 mr-1" />
                                        {{ $deal['expected_close_date'] }}
                                        @if($deal['is_overdue'])
                                            <span class="ml-1 text-xs font-semibold">(Overdue)</span>
                                        @endif
                                    </div>
                                @endif

                                {{-- Days in Stage --}}
                                <div class="flex items-center justify-between text-xs pt-2 border-t border-gray-200 dark:border-gray-600">
                                    @php
                                        $colorClass = match($deal['days_in_stage_color'] ?? 'success') {
                                            'danger' => 'text-red-600 dark:text-red-400 font-semibold',
                                            'warning' => 'text-yellow-600 dark:text-yellow-400 font-medium',
                                            'success' => 'text-green-600 dark:text-green-400',
                                            default => 'text-gray-500 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="{{ $colorClass }}">
                                        {{ $deal['days_in_stage'] }} {{ $deal['days_in_stage'] === 1 ? 'day' : 'days' }} in stage
                                        @if($deal['is_stuck'] ?? false)
                                            <x-heroicon-s-exclamation-triangle class="w-3 h-3 inline ml-1" />
                                        @endif
                                    </span>
                                    <button
                                        wire:click="editDeal({{ $deal['id'] }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                                No deals
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            let draggedDeal = null;
            let originalStage = null;

            // Add drag event listeners to all deal cards
            function setupDragAndDrop() {
                document.querySelectorAll('.deal-card').forEach(card => {
                    card.addEventListener('dragstart', (e) => {
                        draggedDeal = e.target;
                        originalStage = e.target.closest('.deal-stage').dataset.stage;
                        e.target.classList.add('opacity-50');
                    });

                    card.addEventListener('dragend', (e) => {
                        e.target.classList.remove('opacity-50');
                    });
                });

                // Add drop zones
                document.querySelectorAll('.deal-stage').forEach(stage => {
                    stage.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        stage.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
                    });

                    stage.addEventListener('dragleave', (e) => {
                        stage.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                    });

                    stage.addEventListener('drop', (e) => {
                        e.preventDefault();
                        stage.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');

                        if (draggedDeal) {
                            const dealId = parseInt(draggedDeal.dataset.dealId);
                            const newStage = stage.dataset.stage;

                            if (originalStage !== newStage) {
                                // Dispatch Livewire event to move deal
                                @this.dispatch('deal-moved');
                                @this.moveDeal(dealId, newStage);
                            }

                            draggedDeal = null;
                            originalStage = null;
                        }
                    });
                });
            }

            // Setup on initial load
            setupDragAndDrop();

            // Re-setup after Livewire updates
            Livewire.hook('morph.updated', () => {
                setupDragAndDrop();
            });
        });
    </script>
    @endpush

    <style>
        .deal-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .deal-card:hover {
            transform: translateY(-2px);
        }

        .deal-stage {
            min-height: 100px;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</x-filament-panels::page>
