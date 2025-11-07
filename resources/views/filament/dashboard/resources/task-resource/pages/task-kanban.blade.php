<x-filament-panels::page>
    {{-- Filters Section --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Priority Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Priority
                </label>
                <select
                    wire:model.live="filterValues.priority"
                    multiple
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    @foreach($this->getPriorityOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tags Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tags
                </label>
                <select
                    wire:model.live="filterValues.tags"
                    multiple
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    @foreach($this->getTagOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Goal Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Linked Goal
                </label>
                <select
                    wire:model.live="filterValues.goal"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">All Goals</option>
                    @foreach($this->getGoalOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Overdue Filter --}}
            <div class="flex items-end">
                <label class="flex items-center space-x-2">
                    <input
                        type="checkbox"
                        wire:model.live="filterValues.overdue_only"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                    >
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Overdue Only
                    </span>
                </label>
            </div>
        </div>

        @if(collect($filterValues)->filter()->isNotEmpty())
            <div class="mt-4">
                <button
                    wire:click="clearFilters"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                >
                    Clear all filters
                </button>
            </div>
        @endif
    </div>

    {{-- Kanban Board --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Pending Column --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pending
                </h3>
                <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $statusCounts['pending'] ?? 0 }}
                </span>
            </div>
            <div
                class="space-y-3 min-h-[200px]"
                x-data="kanbanColumn('pending')"
                x-on:task-updated.window="loadTasks()"
                x-on:filters-applied.window="loadTasks()"
            >
                <template x-for="task in tasks" :key="task.id">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-move hover:shadow-lg transition-shadow"
                        draggable="true"
                        x-on:dragstart="dragStart($event, task.id)"
                        x-on:dragend="dragEnd($event)"
                        x-on:dragover.prevent
                        x-on:drop="drop($event, 'pending')"
                    >
                        {{-- Task Card Content --}}
                        <div class="mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="task.title"></h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="task.description"></p>
                        </div>

                        {{-- Priority Badge --}}
                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.priority === 'urgent',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.priority === 'high',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': task.priority === 'medium',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': task.priority === 'low'
                                }"
                                x-text="task.priority.charAt(0).toUpperCase() + task.priority.slice(1)"
                            ></span>

                            <template x-if="task.due_date">
                                <span
                                    class="text-xs px-2 py-1 rounded-full"
                                    :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.is_overdue,
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.is_due_today,
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': !task.is_overdue && !task.is_due_today
                                    }"
                                >
                                    <span x-text="task.is_overdue ? '⚠️ ' + task.due_date : (task.is_due_today ? '🔥 ' + task.due_date : task.due_date)"></span>
                                </span>
                            </template>
                        </div>

                        {{-- Tags --}}
                        <template x-if="task.tags && task.tags.length > 0">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <template x-for="tag in task.tags" :key="tag.name">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded"
                                        :class="{
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': tag.color === 'gray',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': tag.color === 'primary',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': tag.color === 'success',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': tag.color === 'warning',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': tag.color === 'danger',
                                            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200': tag.color === 'info',
                                            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': tag.color === 'purple',
                                            'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200': tag.color === 'pink'
                                        }"
                                        x-text="tag.name"
                                    ></span>
                                </template>
                            </div>
                        </template>

                        {{-- AI Priority --}}
                        <template x-if="task.ai_priority_score">
                            <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"></path>
                                </svg>
                                <span x-text="'AI: ' + task.ai_priority_level + ' (' + task.ai_priority_score + '/100)'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- In Progress Column --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    In Progress
                </h3>
                <span class="bg-blue-200 dark:bg-blue-700 text-blue-700 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $statusCounts['in_progress'] ?? 0 }}
                </span>
            </div>
            <div
                class="space-y-3 min-h-[200px]"
                x-data="kanbanColumn('in_progress')"
                x-on:task-updated.window="loadTasks()"
                x-on:filters-applied.window="loadTasks()"
            >
                <template x-for="task in tasks" :key="task.id">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-move hover:shadow-lg transition-shadow"
                        draggable="true"
                        x-on:dragstart="dragStart($event, task.id)"
                        x-on:dragend="dragEnd($event)"
                        x-on:dragover.prevent
                        x-on:drop="drop($event, 'in_progress')"
                    >
                        {{-- Same task card content as Pending --}}
                        <div class="mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="task.title"></h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="task.description"></p>
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.priority === 'urgent',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.priority === 'high',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': task.priority === 'medium',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': task.priority === 'low'
                                }"
                                x-text="task.priority.charAt(0).toUpperCase() + task.priority.slice(1)"
                            ></span>

                            <template x-if="task.due_date">
                                <span
                                    class="text-xs px-2 py-1 rounded-full"
                                    :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.is_overdue,
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.is_due_today,
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': !task.is_overdue && !task.is_due_today
                                    }"
                                >
                                    <span x-text="task.is_overdue ? '⚠️ ' + task.due_date : (task.is_due_today ? '🔥 ' + task.due_date : task.due_date)"></span>
                                </span>
                            </template>
                        </div>

                        <template x-if="task.tags && task.tags.length > 0">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <template x-for="tag in task.tags" :key="tag.name">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded"
                                        :class="{
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': tag.color === 'gray',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': tag.color === 'primary',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': tag.color === 'success',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': tag.color === 'warning',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': tag.color === 'danger',
                                            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200': tag.color === 'info',
                                            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': tag.color === 'purple',
                                            'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200': tag.color === 'pink'
                                        }"
                                        x-text="tag.name"
                                    ></span>
                                </template>
                            </div>
                        </template>

                        <template x-if="task.ai_priority_score">
                            <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"></path>
                                </svg>
                                <span x-text="'AI: ' + task.ai_priority_level + ' (' + task.ai_priority_score + '/100)'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Completed Column --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Completed
                </h3>
                <span class="bg-green-200 dark:bg-green-700 text-green-700 dark:text-green-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $statusCounts['completed'] ?? 0 }}
                </span>
            </div>
            <div
                class="space-y-3 min-h-[200px]"
                x-data="kanbanColumn('completed')"
                x-on:task-updated.window="loadTasks()"
                x-on:filters-applied.window="loadTasks()"
            >
                <template x-for="task in tasks" :key="task.id">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-move hover:shadow-lg transition-shadow opacity-75"
                        draggable="true"
                        x-on:dragstart="dragStart($event, task.id)"
                        x-on:dragend="dragEnd($event)"
                        x-on:dragover.prevent
                        x-on:drop="drop($event, 'completed')"
                    >
                        {{-- Same task card content --}}
                        <div class="mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-through" x-text="task.title"></h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="task.description"></p>
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.priority === 'urgent',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.priority === 'high',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': task.priority === 'medium',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': task.priority === 'low'
                                }"
                                x-text="task.priority.charAt(0).toUpperCase() + task.priority.slice(1)"
                            ></span>
                        </div>

                        <template x-if="task.tags && task.tags.length > 0">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <template x-for="tag in task.tags" :key="tag.name">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded"
                                        :class="{
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': tag.color === 'gray',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': tag.color === 'primary',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': tag.color === 'success',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': tag.color === 'warning',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': tag.color === 'danger',
                                            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200': tag.color === 'info',
                                            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': tag.color === 'purple',
                                            'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200': tag.color === 'pink'
                                        }"
                                        x-text="tag.name"
                                    ></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cancelled Column --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancelled
                </h3>
                <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $statusCounts['cancelled'] ?? 0 }}
                </span>
            </div>
            <div
                class="space-y-3 min-h-[200px]"
                x-data="kanbanColumn('cancelled')"
                x-on:task-updated.window="loadTasks()"
                x-on:filters-applied.window="loadTasks()"
            >
                <template x-for="task in tasks" :key="task.id">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-move hover:shadow-lg transition-shadow opacity-60"
                        draggable="true"
                        x-on:dragstart="dragStart($event, task.id)"
                        x-on:dragend="dragEnd($event)"
                        x-on:dragover.prevent
                        x-on:drop="drop($event, 'cancelled')"
                    >
                        {{-- Same task card content --}}
                        <div class="mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-through" x-text="task.title"></h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="task.description"></p>
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': task.priority === 'urgent',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': task.priority === 'high',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': task.priority === 'medium',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': task.priority === 'low'
                                }"
                                x-text="task.priority.charAt(0).toUpperCase() + task.priority.slice(1)"
                            ></span>
                        </div>

                        <template x-if="task.tags && task.tags.length > 0">
                            <div class="flex flex-wrap gap-1 mb-2">
                                <template x-for="tag in task.tags" :key="tag.name">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded"
                                        :class="{
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': tag.color === 'gray',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': tag.color === 'primary',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': tag.color === 'success',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': tag.color === 'warning',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': tag.color === 'danger',
                                            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200': tag.color === 'info',
                                            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': tag.color === 'purple',
                                            'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200': tag.color === 'pink'
                                        }"
                                        x-text="tag.name"
                                    ></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Alpine.js Component --}}
    @script
    <script>
        Alpine.data('kanbanColumn', (status) => ({
            tasks: [],
            draggedTaskId: null,

            init() {
                this.loadTasks();
            },

            async loadTasks() {
                // Call Livewire method to get tasks for this status
                this.tasks = await $wire.getTasksByStatus(status);
            },

            dragStart(event, taskId) {
                this.draggedTaskId = taskId;
                event.dataTransfer.effectAllowed = 'move';
                event.target.classList.add('opacity-50');
            },

            dragEnd(event) {
                event.target.classList.remove('opacity-50');
            },

            async drop(event, targetStatus) {
                event.preventDefault();

                if (this.draggedTaskId) {
                    await $wire.updateTaskStatus(this.draggedTaskId, targetStatus);
                    this.draggedTaskId = null;

                    // Reload all columns
                    window.dispatchEvent(new CustomEvent('task-updated'));
                }
            }
        }));
    </script>
    @endscript
</x-filament-panels::page>
