<x-filament-panels::page>
    <div class="space-y-6">
        @if($settings && $settings->is_booking_enabled)
            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-100">
                            Your Booking Page is Live!
                        </h3>
                        <p class="mt-1 text-sm text-primary-700 dark:text-primary-300">
                            Share this link with your clients to let them book appointments:
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-primary-300 dark:border-primary-600 rounded-md">
                                {{ $this->getBookingUrl() }}
                            </code>
                            <x-filament::button
                                size="sm"
                                color="primary"
                                icon="heroicon-o-clipboard-document"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $this->getBookingUrl() }}');
                                    $wire.copyBookingUrl();
                                "
                            >
                                Copy
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="save">
            {{ $this->form }}

            <div class="flex justify-end gap-x-3 mt-6">
                <x-filament::button type="submit">
                    Save Settings
                </x-filament::button>
            </div>
        </form>

        @if($settings && !$settings->is_booking_enabled)
            <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="h-5 w-5 text-warning-400"
                        />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                            Public Booking Disabled
                        </h3>
                        <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                            Your booking page is currently disabled. Enable it above to allow clients to book appointments.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Next Steps
            </h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start">
                    <span class="mr-2">1.</span>
                    <span>Create appointment types in the <a href="{{ route('filament.dashboard.resources.appointment-types.index') }}" class="text-primary-600 hover:underline">Appointment Types</a> section</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">2.</span>
                    <span>Set your availability schedule (coming soon)</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">3.</span>
                    <span>Test your booking page before sharing with clients</span>
                </li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
