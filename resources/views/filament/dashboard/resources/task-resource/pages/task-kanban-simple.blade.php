<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Task Board</h1>
        <p class="text-gray-600 dark:text-gray-400">This is a test of the kanban board page.</p>
        
        <div class="mt-4">
            <a href="{{ route('filament.dashboard.resources.tasks.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to List View
            </a>
        </div>
    </div>
</x-filament-panels::page>
