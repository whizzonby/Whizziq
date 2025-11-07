<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-sparkles"
                    class="h-5 w-5 text-primary-500"
                />
                <span>AI Assistant - Ask anything about your data</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Query Input -->
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="query"
                        wire:keydown.enter="processQuery"
                        placeholder="Ask anything about your business data... (e.g., 'What is my current cash flow?')"
                        class="w-full"
                    />
                </x-filament::input.wrapper>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="processQuery"
                    wire:loading.attr="disabled"
                    color="primary"
                >
                    <span wire:loading.remove wire:target="processQuery">Ask AI</span>
                    <span wire:loading wire:target="processQuery">Processing...</span>
                </x-filament::button>

                <x-filament::button
                    wire:click="clearQuery"
                    color="gray"
                    outlined
                >
                    Clear
                </x-filament::button>
            </div>

            <!-- Suggested Questions -->
            @if(!$response)
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Suggested questions:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->getSuggestedQuestions() as $suggestion)
                            <x-filament::button
                                wire:click="$set('query', '{{ $suggestion }}')"
                                size="xs"
                                color="gray"
                                outlined
                            >
                                {{ $suggestion }}
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Response -->
            @if($response)
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 mt-4">
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            icon="heroicon-o-chat-bubble-left-right"
                            class="h-5 w-5 text-primary-500 flex-shrink-0 mt-0.5"
                        />
                        <div class="flex-1 prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($response)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Loading State -->
            <div wire:loading wire:target="processQuery" class="text-center py-4">
                <div class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <x-filament::loading-indicator class="h-4 w-4"/>
                    <span>AI is analyzing your data...</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
