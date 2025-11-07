<x-filament-panels::page>
    <div class="max-w-3xl mx-auto">
        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <x-filament::icon icon="heroicon-s-flag" class="h-8 w-8 text-primary-600" />
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $record->title }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->progress_percentage }}% complete • {{ $record->days_remaining }} days left</p>
                </div>
            </div>
        </div>

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-between">
                <x-filament::button
                    color="gray"
                    tag="a"
                    :href="$this->getResource()::getUrl('view', ['record' => $record])"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button type="submit">
                    Save Check-in
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
