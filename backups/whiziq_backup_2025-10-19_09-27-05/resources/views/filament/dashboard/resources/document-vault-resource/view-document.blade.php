<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Document Header Card --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-sm">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        {{-- File Icon --}}
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl {{ $record->file_color === 'danger' ? 'bg-danger-100 dark:bg-danger-900' : ($record->file_color === 'success' ? 'bg-success-100 dark:bg-success-900' : ($record->file_color === 'warning' ? 'bg-warning-100 dark:bg-warning-900' : ($record->file_color === 'primary' ? 'bg-primary-100 dark:bg-primary-900' : 'bg-gray-100 dark:bg-gray-800'))) }}">
                            <x-filament::icon
                                :icon="$record->file_icon"
                                class="h-8 w-8 {{ $record->file_color === 'danger' ? 'text-danger-600 dark:text-danger-400' : ($record->file_color === 'success' ? 'text-success-600 dark:text-success-400' : ($record->file_color === 'warning' ? 'text-warning-600 dark:text-warning-400' : ($record->file_color === 'primary' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400'))) }}"
                            />
                        </div>

                        {{-- Document Info --}}
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $record->title }}
                            </h2>

                            @if($record->description)
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $record->description }}
                            </p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                {{-- Category Badge --}}
                                @if($record->category)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $record->category_color === 'success' ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300' : ($record->category_color === 'warning' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300' : ($record->category_color === 'primary' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300')) }}">
                                    <x-filament::icon :icon="$record->category_icon" class="h-3.5 w-3.5" />
                                    {{ str_replace('_', ' ', Str::title($record->category)) }}
                                </span>
                                @endif

                                {{-- File Type --}}
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ strtoupper($record->file_type) }} • {{ $record->file_size_human }}
                                </span>

                                {{-- AI Analyzed Badge --}}
                                @if($record->isAnalyzed())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-100 px-3 py-1 text-xs font-medium text-success-700 dark:bg-success-900 dark:text-success-300">
                                    <x-filament::icon icon="heroicon-s-sparkles" class="h-3.5 w-3.5" />
                                    AI Analyzed
                                </span>
                                @endif
                            </div>

                            {{-- Tags --}}
                            @if($record->tags && count($record->tags) > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($record->tags as $tag)
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    #{{ $tag }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Favorite Star --}}
                    @if($record->is_favorite)
                    <x-filament::icon icon="heroicon-s-star" class="h-6 w-6 text-warning-500" />
                    @endif
                </div>

                {{-- Stats Row --}}
                <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-200 dark:border-gray-700 pt-6 sm:grid-cols-4">
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Uploaded</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Downloads</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->download_count }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Accessed</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $record->last_accessed_at ? $record->last_accessed_at->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">File Name</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate" title="{{ $record->file_name }}">
                            {{ Str::limit($record->file_name, 20) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Analysis Section --}}
        @if($record->isAnalyzed())
        <div class="overflow-hidden rounded-xl border border-primary-200 dark:border-primary-800 bg-gradient-to-br from-primary-50 to-white dark:from-primary-900/20 dark:to-gray-900 shadow-sm">
            <div class="border-b border-primary-200 dark:border-primary-800 bg-primary-100/50 dark:bg-primary-900/30 px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-s-sparkles" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    <h3 class="text-lg font-bold text-primary-900 dark:text-primary-100">AI Analysis</h3>
                    <span class="text-xs text-primary-600 dark:text-primary-400">
                        Analyzed {{ $record->analyzed_at->diffForHumans() }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Executive Summary --}}
                @if($record->ai_summary)
                <div>
                    <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4" />
                        Executive Summary
                    </h4>
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            {{ $record->ai_summary }}
                        </p>
                    </div>
                </div>
                @endif

                {{-- Key Points --}}
                @if($record->ai_key_points)
                <div>
                    <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        <x-filament::icon icon="heroicon-o-list-bullet" class="h-4 w-4" />
                        Key Points
                    </h4>
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700">
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($record->ai_key_points)) !!}
                        </div>
                    </div>
                </div>
                @endif

                {{-- Detailed Analysis --}}
                @if($record->ai_analysis && is_array($record->ai_analysis) && count($record->ai_analysis) > 0)
                <div>
                    <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />
                        Detailed Analysis
                    </h4>
                    <div class="space-y-4">
                        @foreach($record->ai_analysis as $section => $content)
                        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $section }}
                            </h5>
                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                {!! nl2br(e($content)) !!}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @else
        {{-- No Analysis Yet --}}
        @if(config('services.openai.key'))
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="p-8 text-center">
                <x-filament::icon icon="heroicon-o-sparkles" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" />
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                    AI Analysis Not Yet Performed
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Click the "AI Analyze" button above to generate an intelligent analysis of this document.
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    AI will extract key information, summarize content, and provide detailed insights.
                </p>
            </div>
        </div>
        @endif
        @endif

        {{-- Document Preview (for supported types) --}}
        @if($record->isImage())
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-photo" class="h-5 w-5" />
                    Preview
                </h3>
            </div>
            <div class="p-6">
                <img
                    src="{{ $record->file_url }}"
                    alt="{{ $record->title }}"
                    class="mx-auto max-h-96 rounded-lg shadow-md"
                />
            </div>
        </div>
        @elseif($record->isPdf())
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                    PDF Preview
                </h3>
            </div>
            <div class="p-6">
                <iframe
                    src="{{ $record->file_url }}"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600"
                    style="height: 600px;"
                ></iframe>
            </div>
        </div>
        @endif

        {{-- Security Notice --}}
        <div class="flex items-start gap-3 rounded-lg border border-info-200 dark:border-info-800 bg-info-50 dark:bg-info-900/20 p-4">
            <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-info-600 dark:text-info-400 mt-0.5" />
            <div class="flex-1">
                <p class="text-sm font-medium text-info-900 dark:text-info-100">Secure Storage</p>
                <p class="mt-1 text-xs text-info-700 dark:text-info-300">
                    Your document is encrypted and stored securely. Only you can access this file. Downloads and access are tracked for your security.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
