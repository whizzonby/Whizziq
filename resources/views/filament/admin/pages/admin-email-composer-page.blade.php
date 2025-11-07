<x-filament-panels::page>
    <form wire:submit="sendEmail">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="submit"
                color="primary"
                size="lg"
            >
                <x-heroicon-o-paper-airplane class="w-5 h-5 mr-2" />
                Send Email
            </x-filament::button>
        </div>
    </form>

    {{-- Email Preview Modal --}}
    @if($showPreviewModal && $previewSubject && $previewBody)
        <x-filament::modal id="email-preview" width="3xl">
            <x-slot name="heading">
                Email Preview
            </x-slot>

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Subject:</h3>
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $previewSubject }}</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email Body:</h3>
                    <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg prose dark:prose-invert max-w-none">
                        {!! $previewBody !!}
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                    This preview shows how the email will look when sent to users.
                </div>
            </div>
        </x-filament::modal>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
