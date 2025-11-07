<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-flag"
                    class="h-5 w-5 text-primary-500"
                />
                <span class="font-bold">Goal Progress Towards Targets</span>
            </div>
        </x-slot>

        @if($activeGoals && count($activeGoals) > 0)
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($activeGoals as $goal)
                    <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow
                        @if($goal['status'] === 'at-risk' || $goal['status'] === 'overdue')
                            border-danger-200 dark:border-danger-800 bg-danger-50 dark:bg-danger-950
                        @elseif($goal['status'] === 'behind' || $goal['status'] === 'needs-attention')
                            border-warning-200 dark:border-warning-800 bg-warning-50 dark:bg-warning-950
                        @elseif($goal['status'] === 'completed')
                            border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-950
                        @else
                            border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                        @endif
                    ">
                        {{-- Goal Header --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $goal['title'] }}
                                </h4>
                                @if($goal['description'])
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ $goal['description'] }}
                                    </p>
                                @endif
                            </div>
                            <x-filament::icon
                                :icon="getStatusIcon($goal['status'])"
                                :class="'h-5 w-5 flex-shrink-0 ml-2 text-' . getStatusColor($goal['status']) . '-600 dark:text-' . getStatusColor($goal['status']) . '-400'"
                            />
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Progress
                                </span>
                                <span class="text-xs font-bold
                                    @if($goal['progress'] >= 100)
                                        text-success-600 dark:text-success-400
                                    @elseif($goal['progress'] >= 70)
                                        text-primary-600 dark:text-primary-400
                                    @elseif($goal['progress'] >= 40)
                                        text-warning-600 dark:text-warning-400
                                    @else
                                        text-danger-600 dark:text-danger-400
                                    @endif
                                ">
                                    {{ number_format($goal['progress'], 1) }}%
                                </span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500
                                        @if($goal['progress'] >= 100)
                                            bg-success-500
                                        @elseif($goal['progress'] >= 70)
                                            bg-primary-500
                                        @elseif($goal['progress'] >= 40)
                                            bg-warning-500
                                        @else
                                            bg-danger-500
                                        @endif
                                    "
                                    style="width: {{ min(100, $goal['progress']) }}%"
                                ></div>
                            </div>
                        </div>

                        {{-- Goal Values --}}
                        <div class="flex items-center justify-between mb-3 text-xs">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Current:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    @if(is_numeric($goal['current_value']))
                                        ${{ number_format($goal['current_value'], 0) }}
                                    @else
                                        {{ $goal['current_value'] }}
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Target:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    @if(is_numeric($goal['target_value']))
                                        ${{ number_format($goal['target_value'], 0) }}
                                    @else
                                        {{ $goal['target_value'] }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Status Badge and Deadline --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <x-filament::badge :color="getStatusColor($goal['status'])" size="sm">
                                {{ getStatusLabel($goal['status']) }}
                            </x-filament::badge>
                            <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-calendar" class="h-3 w-3"/>
                                @if($goal['days_remaining'] > 0)
                                    {{ $goal['days_remaining'] }} {{ $goal['days_remaining'] === 1 ? 'day' : 'days' }} left
                                @elseif($goal['days_remaining'] === 0)
                                    Due today
                                @else
                                    Overdue
                                @endif
                            </div>
                        </div>

                        {{-- Key Results --}}
                        @if(count($goal['key_results']) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Key Results:
                                </p>
                                <div class="space-y-2">
                                    @foreach($goal['key_results'] as $kr)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600 dark:text-gray-400 flex-1 truncate">
                                                {{ $kr['title'] }}
                                            </span>
                                            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                                <span class="font-medium
                                                    @if($kr['status'] === 'completed')
                                                        text-success-600 dark:text-success-400
                                                    @elseif($kr['status'] === 'on-track')
                                                        text-primary-600 dark:text-primary-400
                                                    @elseif($kr['status'] === 'behind')
                                                        text-warning-600 dark:text-warning-400
                                                    @else
                                                        text-danger-600 dark:text-danger-400
                                                    @endif
                                                ">
                                                    {{ number_format($kr['progress'], 0) }}%
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Summary Stats --}}
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-4 gap-4 text-center">
                    @php
                        $totalGoals = count($activeGoals);
                        $onTrack = collect($activeGoals)->where('status', 'on-track')->count();
                        $completed = collect($activeGoals)->where('status', 'completed')->count();
                        $atRisk = collect($activeGoals)->where('status', 'at-risk')->count() + collect($activeGoals)->where('status', 'overdue')->count();
                        $avgProgress = collect($activeGoals)->avg('progress');
                    @endphp

                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalGoals }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Active Goals</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $onTrack + $completed }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">On Track</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $atRisk }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">At Risk</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($avgProgress, 0) }}%</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Avg Progress</p>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-flag"
                    class="h-12 w-12 mx-auto text-gray-400"
                />
                <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-400">
                    No active goals
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    Create goals to track your business targets and progress
                </p>
                <x-filament::button
                    href="{{ route('filament.dashboard.resources.goals.create') }}"
                    size="sm"
                    class="mt-4"
                >
                    Create Your First Goal
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
