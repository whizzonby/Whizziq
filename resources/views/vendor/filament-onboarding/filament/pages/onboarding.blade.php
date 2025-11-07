<x-filament-panels::page>
    <div class="relative">
        @if (\Saasykit\FilamentOnboarding\FilamentOnboardingPlugin::get()->isSkippable())
            <a wire:click="skip" class="cursor-pointer text-sm text-neutral-500 hover:text-neutral-900 absolute top-0 right-0 -mt-16 -mr-4">
                {{ __('Skip Onboarding') }}
            </a>
        @endif

        <h1 class="text-3xl font-bold text-center">{{ __('Welcome to :app_name', ['app_name' => config('app.name')]) }}!</h1>
        <p class="text-center mt-4 text-neutral-500">{{ __('Let\'s get started by setting up your account.') }}</p>
    </div>
    <form wire:submit="submit">
        {{ $this->form }}
        <div>
            <div class="flex items-center justify-center flex-col gap-6">

                <x-filament::button type="submit" size="xl" class="mt-4">
                    {{ __('Submit') }}
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
