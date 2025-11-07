<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bell-alert class="w-5 h-5" />
                <span>Smart Notifications</span>
                @if($this->getNotificationCount() > 0)
                    <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-600 rounded-full">
                        {{ $this->getNotificationCount() }}
                    </span>
                @endif
            </div>
        </x-slot>

        @php
            $notifications = $this->getNotifications();
        @endphp

        @if($notifications->isEmpty())
            <div class="text-center py-8">
                <x-heroicon-o-check-circle class="w-12 h-12 text-green-500 mx-auto mb-3" />
                <p class="text-gray-600 dark:text-gray-400">All caught up! No pending notifications.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($notifications as $notification)
                    <div class="flex items-start gap-3 p-4 rounded-lg border
                        {{ $notification['priority'] === 'high' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : '' }}
                        {{ $notification['priority'] === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : '' }}
                        {{ $notification['priority'] === 'low' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : '' }}">

                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                            {{ $notification['color'] === 'danger' ? 'bg-red-100 dark:bg-red-900/30' : '' }}
                            {{ $notification['color'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : '' }}
                            {{ $notification['color'] === 'info' ? 'bg-blue-100 dark:bg-blue-900/30' : '' }}
                            {{ $notification['color'] === 'success' ? 'bg-green-100 dark:bg-green-900/30' : '' }}">
                            <x-dynamic-component
                                :component="$notification['icon']"
                                class="w-5 h-5
                                {{ $notification['color'] === 'danger' ? 'text-red-600 dark:text-red-400' : '' }}
                                {{ $notification['color'] === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                                {{ $notification['color'] === 'info' ? 'text-blue-600 dark:text-blue-400' : '' }}
                                {{ $notification['color'] === 'success' ? 'text-green-600 dark:text-green-400' : '' }}"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold
                                        {{ $notification['priority'] === 'high' ? 'text-red-900 dark:text-red-100' : '' }}
                                        {{ $notification['priority'] === 'medium' ? 'text-yellow-900 dark:text-yellow-100' : '' }}
                                        {{ $notification['priority'] === 'low' ? 'text-blue-900 dark:text-blue-100' : '' }}">
                                        {{ $notification['title'] }}
                                    </p>
                                    <p class="text-sm mt-1
                                        {{ $notification['priority'] === 'high' ? 'text-red-700 dark:text-red-300' : '' }}
                                        {{ $notification['priority'] === 'medium' ? 'text-yellow-700 dark:text-yellow-300' : '' }}
                                        {{ $notification['priority'] === 'low' ? 'text-blue-700 dark:text-blue-300' : '' }}">
                                        {{ $notification['message'] }}
                                    </p>
                                </div>

                                @if($notification['priority'] === 'high')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-600 text-white">
                                        URGENT
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 mt-3">
                                <a
                                    href="{{ $notification['action_url'] }}"
                                    class="text-sm font-medium
                                    {{ $notification['color'] === 'danger' ? 'text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300' : '' }}
                                    {{ $notification['color'] === 'warning' ? 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300' : '' }}
                                    {{ $notification['color'] === 'info' ? 'text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300' : '' }}
                                    {{ $notification['color'] === 'success' ? 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300' : '' }}"
                                >
                                    {{ $notification['action_text'] }} →
                                </a>

                                @if(isset($notification['created_at']))
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $notification['created_at']->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($this->getNotificationCount() > 10)
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Showing 10 of {{ $this->getNotificationCount() }} notifications
                    </p>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
