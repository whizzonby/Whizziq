<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-squares-2x2"
                        class="h-5 w-5 text-primary-500"
                    />
                    <span class="font-bold">SWOT Analysis - Strategic Overview</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="generateAISwot"
                        wire:loading.attr="disabled"
                        size="sm"
                        color="primary"
                    >
                        <x-filament::icon
                            icon="heroicon-m-sparkles"
                            class="h-4 w-4 mr-1"
                            wire:loading.class="animate-spin"
                            wire:target="generateAISwot"
                        />
                        AI-Generate SWOT
                    </x-filament::button>
                </div>
            </div>
        </x-slot>

        {{-- Strategic Insights Panel --}}
        @if($strategicInsights && count($strategicInsights) > 0)
            <div class="mb-6 p-4 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950 dark:to-primary-900 rounded-lg border border-primary-200 dark:border-primary-800">
                <div class="flex items-center gap-2 mb-3">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 text-primary-600 dark:text-primary-400"/>
                    <h3 class="text-sm font-bold text-primary-900 dark:text-primary-100">Key Strategic Takeaways</h3>
                </div>
                <div class="space-y-2">
                    @foreach($strategicInsights as $insight)
                        <div class="flex items-start gap-2 text-sm">
                            <x-filament::badge :color="$insight['type']" size="sm" class="mt-0.5">
                                {{ ucfirst($insight['type']) }}
                            </x-filament::badge>
                            <div class="flex-1">
                                <span class="font-semibold text-primary-900 dark:text-primary-100">{{ $insight['title'] }}:</span>
                                <span class="text-primary-800 dark:text-primary-200">{{ $insight['description'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SWOT Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- STRENGTHS Quadrant --}}
            <div class="border-2 border-success-300 dark:border-success-700 rounded-lg overflow-hidden">
                <div class="bg-success-100 dark:bg-success-950 p-3 border-b-2 border-success-300 dark:border-success-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-5 w-5 text-success-600 dark:text-success-400"/>
                            <h3 class="text-sm font-bold text-success-900 dark:text-success-100">STRENGTHS</h3>
                            <x-filament::badge color="success" size="sm">
                                {{ count($swotData['strengths']) }}
                            </x-filament::badge>
                        </div>
                        <x-filament::icon-button
                            icon="heroicon-m-plus"
                            wire:click="openAddForm('strength')"
                            size="sm"
                            color="success"
                            tooltip="Add Strength"
                        />
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 min-h-[200px]">
                    @if(count($swotData['strengths']) > 0)
                        <div class="space-y-2">
                            @foreach($swotData['strengths'] as $item)
                                <div class="flex items-start gap-2 p-3 rounded-lg bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800 hover:shadow-md transition-shadow group">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-filament::badge :color="$this->getPriorityColor($item['priority'])" size="xs">
                                                {{ $this->getPriorityLabel($item['priority']) }}
                                            </x-filament::badge>
                                            <span class="text-xs text-gray-500">{{ $item['created_at'] }}</span>
                                        </div>
                                        <p class="text-sm text-success-900 dark:text-success-100">{{ $item['description'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-filament::icon-button
                                            icon="heroicon-m-pencil"
                                            wire:click="editSwotItem({{ $item['id'] }})"
                                            size="xs"
                                            color="gray"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            wire:click="deleteSwotItem({{ $item['id'] }})"
                                            wire:confirm="Are you sure you want to delete this item?"
                                            size="xs"
                                            color="danger"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-[180px] text-center">
                            <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-12 w-12 text-gray-400 mb-2"/>
                            <p class="text-sm text-gray-500">No strengths added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click + to add or use AI generation</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- WEAKNESSES Quadrant --}}
            <div class="border-2 border-danger-300 dark:border-danger-700 rounded-lg overflow-hidden">
                <div class="bg-danger-100 dark:bg-danger-950 p-3 border-b-2 border-danger-300 dark:border-danger-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-arrow-trending-down" class="h-5 w-5 text-danger-600 dark:text-danger-400"/>
                            <h3 class="text-sm font-bold text-danger-900 dark:text-danger-100">WEAKNESSES</h3>
                            <x-filament::badge color="danger" size="sm">
                                {{ count($swotData['weaknesses']) }}
                            </x-filament::badge>
                        </div>
                        <x-filament::icon-button
                            icon="heroicon-m-plus"
                            wire:click="openAddForm('weakness')"
                            size="sm"
                            color="danger"
                            tooltip="Add Weakness"
                        />
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 min-h-[200px]">
                    @if(count($swotData['weaknesses']) > 0)
                        <div class="space-y-2">
                            @foreach($swotData['weaknesses'] as $item)
                                <div class="flex items-start gap-2 p-3 rounded-lg bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800 hover:shadow-md transition-shadow group">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-filament::badge :color="$this->getPriorityColor($item['priority'])" size="xs">
                                                {{ $this->getPriorityLabel($item['priority']) }}
                                            </x-filament::badge>
                                            <span class="text-xs text-gray-500">{{ $item['created_at'] }}</span>
                                        </div>
                                        <p class="text-sm text-danger-900 dark:text-danger-100">{{ $item['description'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-filament::icon-button
                                            icon="heroicon-m-pencil"
                                            wire:click="editSwotItem({{ $item['id'] }})"
                                            size="xs"
                                            color="gray"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            wire:click="deleteSwotItem({{ $item['id'] }})"
                                            wire:confirm="Are you sure you want to delete this item?"
                                            size="xs"
                                            color="danger"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-[180px] text-center">
                            <x-filament::icon icon="heroicon-o-arrow-trending-down" class="h-12 w-12 text-gray-400 mb-2"/>
                            <p class="text-sm text-gray-500">No weaknesses added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click + to add or use AI generation</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- OPPORTUNITIES Quadrant --}}
            <div class="border-2 border-warning-300 dark:border-warning-700 rounded-lg overflow-hidden">
                <div class="bg-warning-100 dark:bg-warning-950 p-3 border-b-2 border-warning-300 dark:border-warning-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 text-warning-600 dark:text-warning-400"/>
                            <h3 class="text-sm font-bold text-warning-900 dark:text-warning-100">OPPORTUNITIES</h3>
                            <x-filament::badge color="warning" size="sm">
                                {{ count($swotData['opportunities']) }}
                            </x-filament::badge>
                        </div>
                        <x-filament::icon-button
                            icon="heroicon-m-plus"
                            wire:click="openAddForm('opportunity')"
                            size="sm"
                            color="warning"
                            tooltip="Add Opportunity"
                        />
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 min-h-[200px]">
                    @if(count($swotData['opportunities']) > 0)
                        <div class="space-y-2">
                            @foreach($swotData['opportunities'] as $item)
                                <div class="flex items-start gap-2 p-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800 hover:shadow-md transition-shadow group">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-filament::badge :color="$this->getPriorityColor($item['priority'])" size="xs">
                                                {{ $this->getPriorityLabel($item['priority']) }}
                                            </x-filament::badge>
                                            <span class="text-xs text-gray-500">{{ $item['created_at'] }}</span>
                                        </div>
                                        <p class="text-sm text-warning-900 dark:text-warning-100">{{ $item['description'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-filament::icon-button
                                            icon="heroicon-m-pencil"
                                            wire:click="editSwotItem({{ $item['id'] }})"
                                            size="xs"
                                            color="gray"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            wire:click="deleteSwotItem({{ $item['id'] }})"
                                            wire:confirm="Are you sure you want to delete this item?"
                                            size="xs"
                                            color="danger"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-[180px] text-center">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="h-12 w-12 text-gray-400 mb-2"/>
                            <p class="text-sm text-gray-500">No opportunities added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click + to add or use AI generation</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- THREATS Quadrant --}}
            <div class="border-2 border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="bg-gray-100 dark:bg-gray-800 p-3 border-b-2 border-gray-300 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-gray-600 dark:text-gray-400"/>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">THREATS</h3>
                            <x-filament::badge color="gray" size="sm">
                                {{ count($swotData['threats']) }}
                            </x-filament::badge>
                        </div>
                        <x-filament::icon-button
                            icon="heroicon-m-plus"
                            wire:click="openAddForm('threat')"
                            size="sm"
                            color="gray"
                            tooltip="Add Threat"
                        />
                    </div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 min-h-[200px]">
                    @if(count($swotData['threats']) > 0)
                        <div class="space-y-2">
                            @foreach($swotData['threats'] as $item)
                                <div class="flex items-start gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow group">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-filament::badge :color="$this->getPriorityColor($item['priority'])" size="xs">
                                                {{ $this->getPriorityLabel($item['priority']) }}
                                            </x-filament::badge>
                                            <span class="text-xs text-gray-500">{{ $item['created_at'] }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $item['description'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-filament::icon-button
                                            icon="heroicon-m-pencil"
                                            wire:click="editSwotItem({{ $item['id'] }})"
                                            size="xs"
                                            color="gray"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-m-trash"
                                            wire:click="deleteSwotItem({{ $item['id'] }})"
                                            wire:confirm="Are you sure you want to delete this item?"
                                            size="xs"
                                            color="danger"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-[180px] text-center">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-12 w-12 text-gray-400 mb-2"/>
                            <p class="text-sm text-gray-500">No threats added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click + to add or use AI generation</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Add/Edit Form Modal --}}
        @if($showAddForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click.self="cancelForm">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                        {{ $editingId ? 'Edit' : 'Add' }} {{ ucfirst($addFormType) }}
                    </h3>

                    <form wire:submit="saveSwotItem" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description *
                            </label>
                            <textarea
                                wire:model="addFormDescription"
                                rows="4"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Describe this {{ $addFormType }}..."
                            ></textarea>
                            @error('addFormDescription')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Priority *
                            </label>
                            <select
                                wire:model="addFormPriority"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="5">Critical (5)</option>
                                <option value="4">High (4)</option>
                                <option value="3">Medium (3)</option>
                                <option value="2">Low (2)</option>
                                <option value="1">Very Low (1)</option>
                            </select>
                            @error('addFormPriority')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                            <x-filament::button
                                type="button"
                                wire:click="cancelForm"
                                color="gray"
                                outlined
                            >
                                Cancel
                            </x-filament::button>
                            <x-filament::button type="submit" color="primary">
                                {{ $editingId ? 'Update' : 'Add' }} {{ ucfirst($addFormType) }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Footer Stats --}}
        @php
            $stats = $this->getQuadrantStats();
        @endphp
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-4">
                    <span>Total Items: <strong>{{ $stats['total_items'] }}</strong></span>
                    <span>High Priority: <strong>{{ $stats['high_priority_count'] }}</strong></span>
                </div>
                <span class="flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-information-circle" class="h-3 w-3"/>
                    Keep your SWOT analysis updated for better strategic planning
                </span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
