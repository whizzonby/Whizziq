<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            {{ $this->getFormActions() }}
        </div>
    </form>
</x-filament-panels::page>
