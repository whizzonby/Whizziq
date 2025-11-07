<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Task Board</h1>
            <a href="{{ route('filament.dashboard.resources.tasks.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                List View
            </a>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Pending</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statusCounts['pending'] ?? 0 }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-sm text-blue-600 dark:text-blue-400">In Progress</div>
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $statusCounts['in_progress'] ?? 0 }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-sm text-green-600 dark:text-green-400">Completed</div>
                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $statusCounts['completed'] ?? 0 }}</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <div class="text-sm text-red-600 dark:text-red-400">Cancelled</div>
                <div class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $statusCounts['cancelled'] ?? 0 }}</div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="grid grid-cols-4 gap-4">
            <!-- Pending Column -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-2"></span>
                    Pending
                </h3>
                <div class="space-y-3">
                    @foreach($this->getTasksByStatus('pending') as $task)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition cursor-pointer"
                             wire:click="editTask({{ $task['id'] }})">
                            <div class="font-medium text-gray-900 dark:text-white mb-1">{{ $task['title'] }}</div>
                            @if($task['due_date'])
                                <div class="text-xs {{ $task['is_overdue'] ? 'text-red-600' : 'text-gray-500' }}">
                                    Due: {{ $task['due_date'] }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $task['priority_color'] }}-100 text-{{ $task['priority_color'] }}-800">
                                    {{ ucfirst($task['priority']) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- In Progress Column -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    In Progress
                </h3>
                <div class="space-y-3">
                    @foreach($this->getTasksByStatus('in_progress') as $task)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-blue-200 dark:border-blue-600 hover:shadow-md transition cursor-pointer"
                             wire:click="editTask({{ $task['id'] }})">
                            <div class="font-medium text-gray-900 dark:text-white mb-1">{{ $task['title'] }}</div>
                            @if($task['due_date'])
                                <div class="text-xs {{ $task['is_overdue'] ? 'text-red-600' : 'text-gray-500' }}">
                                    Due: {{ $task['due_date'] }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $task['priority_color'] }}-100 text-{{ $task['priority_color'] }}-800">
                                    {{ ucfirst($task['priority']) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Completed Column -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <h3 class="font-semibold text-green-900 dark:text-green-100 mb-4 flex items-center">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    Completed
                </h3>
                <div class="space-y-3">
                    @foreach($this->getTasksByStatus('completed') as $task)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-green-200 dark:border-green-600 hover:shadow-md transition cursor-pointer opacity-75"
                             wire:click="editTask({{ $task['id'] }})">
                            <div class="font-medium text-gray-900 dark:text-white mb-1 line-through">{{ $task['title'] }}</div>
                            @if($task['due_date'])
                                <div class="text-xs text-gray-500">
                                    Was due: {{ $task['due_date'] }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Done
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Cancelled Column -->
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <h3 class="font-semibold text-red-900 dark:text-red-100 mb-4 flex items-center">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                    Cancelled
                </h3>
                <div class="space-y-3">
                    @foreach($this->getTasksByStatus('cancelled') as $task)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-red-200 dark:border-red-600 hover:shadow-md transition cursor-pointer opacity-75"
                             wire:click="editTask({{ $task['id'] }})">
                            <div class="font-medium text-gray-900 dark:text-white mb-1 line-through">{{ $task['title'] }}</div>
                            @if($task['due_date'])
                                <div class="text-xs text-gray-500">
                                    Was due: {{ $task['due_date'] }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    ✗ Cancelled
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
