<x-filament-panels::page>
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <div class="w-4 h-4 rounded-full" style="background-color: {{ $segment->color }}"></div>

            <div class="flex-1">
                <div class="flex items-center gap-2">
                    @if($segment->is_favorite)
                        <x-heroicon-s-star class="w-5 h-5 text-yellow-500" />
                    @endif
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $segment->contact_count }} {{ Str::plural('contact', $segment->contact_count) }} in this segment
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="$dispatch('refresh')"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-arrow-path"
                >
                    Refresh
                </x-filament::button>

                <x-filament::button
                    href="{{ route('filament.dashboard.resources.contact-segments.edit', ['record' => $segment->id]) }}"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-pencil"
                >
                    Edit Segment
                </x-filament::button>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
