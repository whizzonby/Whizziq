<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Goal Header --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-sm">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $record->type === 'annual' ? 'bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300' : ($record->type === 'quarterly' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300') }}">
                                <x-filament::icon :icon="$record->type_icon" class="h-3.5 w-3.5" />
                                {{ Str::title($record->type) }} Goal
                            </span>

                            @if($record->category)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $record->category === 'revenue' ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300' : ($record->category === 'customers' ? 'bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                <x-filament::icon :icon="$record->category_icon" class="h-3.5 w-3.5" />
                                {{ Str::title(str_replace('_', ' ', $record->category)) }}
                            </span>
                            @endif
                        </div>

                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $record->title }}
                        </h2>

                        @if($record->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $record->description }}
                        </p>
                        @endif
                    </div>

                    <div class="text-right">
                        <div class="text-3xl font-bold {{ $record->progress_percentage >= 75 ? 'text-success-600' : ($record->progress_percentage >= 50 ? 'text-primary-600' : 'text-warning-600') }}">
                            {{ $record->progress_percentage }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Complete</div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-6">
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $record->progress_percentage >= 75 ? 'bg-success-500' : ($record->progress_percentage >= 50 ? 'bg-primary-500' : 'bg-warning-500') }} transition-all duration-500" style="width: {{ $record->progress_percentage }}%"></div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="mt-6 grid grid-cols-4 gap-4">
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <p class="mt-1 flex items-center justify-center gap-1 text-sm font-semibold {{ $record->status === 'on_track' || $record->status === 'completed' ? 'text-success-600' : ($record->status === 'at_risk' ? 'text-warning-600' : 'text-danger-600') }}">
                            <x-filament::icon :icon="$record->status_icon" class="h-4 w-4" />
                            {{ Str::title(str_replace('_', ' ', $record->status)) }}
                        </p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Due Date</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->target_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Days Left</p>
                        <p class="mt-1 text-sm font-semibold {{ $record->days_remaining < 7 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $record->days_remaining >= 0 ? $record->days_remaining : 'Overdue' }}
                        </p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Check-ins</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->check_in_count }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Key Results --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5" />
                    Key Results
                </h3>
            </div>
            <div class="p-6 space-y-4">
                @forelse($record->keyResults as $kr)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $kr->title }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $kr->formatted_current_value }} / {{ $kr->formatted_target_value }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kr->status === 'completed' ? 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' : ($kr->status === 'on_track' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200' : 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200') }}">
                            {{ $kr->progress_percentage }}%
                        </span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $kr->progress_percentage >= 75 ? 'bg-success-500' : ($kr->progress_percentage >= 50 ? 'bg-primary-500' : 'bg-warning-500') }} transition-all duration-500" style="width: {{ $kr->progress_percentage }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-4">No key results defined yet</p>
                @endforelse
            </div>
        </div>

        {{-- AI Suggestions (if off-track) --}}
        @if($record->ai_suggestions && in_array($record->status, ['at_risk', 'off_track']))
        <div class="overflow-hidden rounded-xl border border-warning-200 dark:border-warning-800 bg-warning-50 dark:bg-warning-900/20 shadow-sm">
            <div class="border-b border-warning-200 dark:border-warning-800 bg-warning-100/50 dark:bg-warning-900/30 px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-s-sparkles" class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    <h3 class="text-lg font-bold text-warning-900 dark:text-warning-100">AI Recommendations</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="prose prose-sm dark:prose-invert max-w-none text-warning-900 dark:text-warning-100">
                    {!! nl2br(e($record->ai_suggestions)) !!}
                </div>
            </div>
        </div>
        @endif

        {{-- Check-in History --}}
        @if($record->checkIns->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5" />
                    Check-in History
                </h3>
            </div>
            <div class="p-6 space-y-4">
                @foreach($record->checkIns()->latest()->take(5)->get() as $checkIn)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $checkIn->created_at->format('M d, Y') }}</span>
                        @if($checkIn->sentiment)
                        <span class="inline-flex items-center gap-1 text-xs">
                            <x-filament::icon :icon="$checkIn->sentiment_icon" class="h-4 w-4 {{ $checkIn->sentiment === 'positive' ? 'text-success-600' : ($checkIn->sentiment === 'neutral' ? 'text-warning-600' : 'text-danger-600') }}" />
                        </span>
                        @endif
                    </div>
                    @if($checkIn->notes)
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $checkIn->notes }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
