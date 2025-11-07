<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    {{-- Calendar Header --}}
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <button
                wire:click="previousMonth"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 min-w-[150px] text-center">
                {{ $monthName }}
            </h2>

            <button
                wire:click="nextMonth"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <button
            wire:click="goToToday"
            class="px-3 py-1.5 text-sm font-medium text-primary-700 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
        >
            Today
        </button>
    </div>

    {{-- Calendar Grid --}}
    <div class="p-4">
        {{-- Days of Week Header --}}
        <div class="grid grid-cols-7 gap-1 mb-2">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="text-center text-xs font-semibold text-gray-600 dark:text-gray-400 py-2">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Calendar Days --}}
        <div class="grid grid-cols-7 gap-1">
            @foreach($calendarDays as $day)
                <div
                    wire:click="selectDate('{{ $day['dateString'] }}')"
                    class="
                        min-h-[100px] p-2 border rounded-lg cursor-pointer transition-all
                        {{ $day['isToday'] ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700' }}
                        {{ $day['isPast'] ? 'bg-gray-50/50 dark:bg-gray-900/20' : '' }}
                        {{ !$day['isCurrentMonth'] ? 'opacity-40' : '' }}
                        hover:border-primary-400 hover:shadow-md
                    "
                >
                    {{-- Day Number --}}
                    <div class="flex items-center justify-between mb-1">
                        <span class="
                            text-sm font-medium
                            {{ $day['isToday'] ? 'text-primary-700 dark:text-primary-400 font-bold' : 'text-gray-700 dark:text-gray-300' }}
                        ">
                            {{ $day['day'] }}
                        </span>

                        {{-- Appointment Count Badge --}}
                        @if(count($day['appointments']) > 0)
                            <span class="px-1.5 py-0.5 text-xs font-semibold bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-full">
                                {{ count($day['appointments']) }}
                            </span>
                        @endif
                    </div>

                    {{-- Appointments for this day --}}
                    <div class="space-y-1">
                        @foreach(array_slice($day['appointments'], 0, 2) as $appointment)
                            <div
                                class="px-2 py-1 text-xs rounded truncate"
                                style="background-color: {{ $appointment['appointment_type']['color'] ?? '#3B82F6' }}20; border-left: 3px solid {{ $appointment['appointment_type']['color'] ?? '#3B82F6' }}"
                                title="{{ $appointment['title'] }} - {{ \Carbon\Carbon::parse($appointment['start_datetime'])->format('g:i A') }}"
                            >
                                <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ \Carbon\Carbon::parse($appointment['start_datetime'])->format('g:i A') }}
                                </div>
                                <div class="text-gray-600 dark:text-gray-400 truncate">
                                    {{ $appointment['title'] }}
                                </div>
                            </div>
                        @endforeach

                        @if(count($day['appointments']) > 2)
                            <div class="text-xs text-gray-500 dark:text-gray-400 pl-2">
                                +{{ count($day['appointments']) - 2 }} more
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-lg">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
</div>
