<x-filament-panels::page>
    @php
        $task = $this->record;
    @endphp

    <div class="space-y-6">
        {{-- Task Header --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-2">{{ $task->title }}</h2>
                    @if($task->description)
                        <p class="text-blue-100 text-sm">{{ $task->description }}</p>
                    @endif
                </div>
                <div class="ml-4">
                    <x-filament::badge :color="$task->priority_color" size="lg">
                        <x-filament::icon :icon="$task->priority_icon" class="w-4 h-4 mr-1" />
                        {{ Str::title($task->priority) }} Priority
                    </x-filament::badge>
                </div>
            </div>
        </div>

        {{-- Key Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Status</div>
                <x-filament::badge :color="$task->status_color">
                    <x-filament::icon :icon="$task->status_icon" class="w-4 h-4 mr-1" />
                    {{ Str::title(str_replace('_', ' ', $task->status)) }}
                </x-filament::badge>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Due Date</div>
                <div class="font-semibold text-gray-900 dark:text-white">
                    @if($task->due_date)
                        {{ $task->due_date->format('M d, Y') }}
                        @if($task->isOverdue())
                            <x-filament::badge color="danger" size="sm">Overdue</x-filament::badge>
                        @elseif($task->isDueToday())
                            <x-filament::badge color="warning" size="sm">Today</x-filament::badge>
                        @endif
                    @else
                        <span class="text-gray-400">Not set</span>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Estimated Time</div>
                <div class="font-semibold text-gray-900 dark:text-white">
                    {{ $task->estimated_time_human ?? 'Not set' }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Source</div>
                <x-filament::badge color="gray">
                    <x-filament::icon :icon="$task->source_icon" class="w-4 h-4 mr-1" />
                    {{ Str::title(str_replace('_', ' ', $task->source)) }}
                </x-filament::badge>
            </div>
        </div>

        {{-- AI Priority Analysis --}}
        @if($task->hasAIPriority())
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-6 border-2 border-purple-200 dark:border-purple-800">
                <div class="flex items-start gap-3">
                    <x-filament::icon icon="heroicon-s-sparkles" class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-1" />
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
                            AI Priority Analysis
                        </h3>
                        <div class="mb-3">
                            <span class="text-sm text-gray-600 dark:text-gray-300">AI Priority Score: </span>
                            <x-filament::badge :color="$task->ai_priority_score >= 80 ? 'danger' : ($task->ai_priority_score >= 60 ? 'warning' : 'primary')" size="lg">
                                {{ $task->ai_priority_score }}/100 - {{ $task->ai_priority_level }}
                            </x-filament::badge>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $task->ai_priority_reasoning }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tags --}}
        @if($task->tags->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($task->tags as $tag)
                        <x-filament::badge :color="$tag->color">
                            @if($tag->icon)
                                <x-filament::icon :icon="$tag->icon" class="w-4 h-4 mr-1" />
                            @endif
                            {{ $tag->name }}
                        </x-filament::badge>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Linked Resources --}}
        @if($task->linkedGoal || $task->linkedDocument)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-filament::icon icon="heroicon-o-link" class="w-5 h-5 inline mr-2" />
                    Linked Resources
                </h3>
                <div class="space-y-3">
                    @if($task->linkedGoal)
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-3">
                                <x-filament::icon icon="heroicon-o-flag" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->linkedGoal->title }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Business Goal - {{ $task->linkedGoal->progress_percentage }}% Complete</div>
                                </div>
                            </div>
                            <a href="{{ route('filament.dashboard.resources.goals.view', ['record' => $task->linkedGoal]) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                View Goal →
                            </a>
                        </div>
                    @endif

                    @if($task->linkedDocument)
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-3">
                                <x-filament::icon icon="heroicon-o-document" class="w-5 h-5 text-green-600 dark:text-green-400" />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->linkedDocument->file_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Document - {{ $task->linkedDocument->file_size_human }}</div>
                                </div>
                            </div>
                            <a href="{{ route('filament.dashboard.resources.document-vaults.view', ['record' => $task->linkedDocument]) }}" class="text-green-600 hover:text-green-700 text-sm font-medium">
                                View Document →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Additional Notes --}}
        @if($task->notes)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5 inline mr-2" />
                    Notes
                </h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->notes }}</p>
            </div>
        @endif

        {{-- Reminder Info --}}
        @if($task->reminder_enabled && $task->reminder_date)
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-bell" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    <span class="text-sm font-medium text-amber-900 dark:text-amber-100">
                        Reminder set for {{ $task->reminder_date->format('M d, Y \a\t g:i A') }}
                    </span>
                </div>
            </div>
        @endif

        {{-- Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Timeline</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-o-plus-circle" class="w-5 h-5 text-gray-400" />
                    <span class="text-gray-600 dark:text-gray-400">Created {{ $task->created_at->diffForHumans() }}</span>
                    <span class="text-gray-400 dark:text-gray-500 text-xs">{{ $task->created_at->format('M d, Y g:i A') }}</span>
                </div>

                @if($task->updated_at != $task->created_at)
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-pencil" class="w-5 h-5 text-gray-400" />
                        <span class="text-gray-600 dark:text-gray-400">Last updated {{ $task->updated_at->diffForHumans() }}</span>
                        <span class="text-gray-400 dark:text-gray-500 text-xs">{{ $task->updated_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif

                @if($task->completed_at)
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-green-500" />
                        <span class="text-gray-600 dark:text-gray-400">Completed {{ $task->completed_at->diffForHumans() }}</span>
                        <span class="text-gray-400 dark:text-gray-500 text-xs">{{ $task->completed_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
