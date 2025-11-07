<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Side: Form -->
        <div class="space-y-6">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <x-heroicon-o-sparkles class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Beautiful Invoice Builder</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 ml-13">Create stunning, professional invoices in minutes with live preview</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                {{ $this->form }}
            </div>

            <div class="flex gap-3">
                <x-filament::button
                    wire:click="saveDraft"
                    color="gray"
                    icon="heroicon-o-document"
                    class="flex-1"
                >
                    Save as Draft
                </x-filament::button>

                {{-- Preview PDF button commented out - using live preview instead --}}
                {{-- <x-filament::button
                    wire:click="previewPDF"
                    color="info"
                    icon="heroicon-o-eye"
                    class="flex-1"
                >
                    Preview PDF
                </x-filament::button> --}}

                <x-filament::button
                    wire:click="saveAndSend"
                    color="success"
                    icon="heroicon-o-paper-airplane"
                    class="flex-1"
                >
                    Save & Send
                </x-filament::button>
            </div>

            <!-- View All Invoices Link -->
            <div class="text-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('filament.dashboard.resources.client-invoices.index') }}"
                   class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 inline-flex items-center gap-2 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    View All Invoices
                </a>
            </div>
        </div>

        <!-- Right Side: Live Preview -->
        <div class="lg:sticky lg:top-6 lg:self-start">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                <!-- Preview Header -->
                <div class="bg-gradient-to-r from-gray-900 to-gray-700 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-eye class="w-5 h-5 text-gray-300" />
                        <span class="text-white font-semibold">Live Preview</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-medium">
                            Real-time
                        </span>
                    </div>
                </div>

                <!-- Invoice Preview -->
                <div class="p-8 bg-gray-50 dark:bg-gray-900 min-h-[800px]" id="invoice-preview">
                    @include('filament.dashboard.components.invoice-preview', [
                        'data' => $data,
                        'template' => $template,
                        'primaryColor' => $primaryColor,
                        'accentColor' => $accentColor,
                    ])
                </div>

                <!-- Preview Footer -->
                <div class="bg-gray-100 dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            Template: <span class="font-semibold">{{ ucfirst($template) }}</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $primaryColor }}"></div>
                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $accentColor }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-light-bulb class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div class="text-sm">
                        <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Pro Tips:</p>
                        <ul class="text-blue-700 dark:text-blue-300 space-y-1 list-disc list-inside">
                            <li>Changes update in real-time on the right</li>
                            <li>Choose from 4 beautiful templates</li>
                            <li>Customize colors to match your brand</li>
                            <li>Preview PDF before sending</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh preview when form changes
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', ({ component }) => {
                // Trigger preview update animation
                const preview = document.getElementById('invoice-preview');
                if (preview) {
                    preview.style.opacity = '0.7';
                    setTimeout(() => {
                        preview.style.opacity = '1';
                    }, 150);
                }
            });
        });
    </script>
    @endpush

    <style>
        #invoice-preview {
            transition: opacity 0.15s ease-in-out;
        }
    </style>
</x-filament-panels::page>
