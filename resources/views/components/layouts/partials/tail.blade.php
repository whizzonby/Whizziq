@stack('tail')

@vite(['resources/js/app.js'])

@include('components.layouts.partials.analytics')
@include('components.layouts.partials.tawkto')

@php($skipCookieContentBar = $skipCookieContentBar ?? false)

@if (!$skipCookieContentBar)
    @include('cookie-consent::index')
@endif

@livewireScripts
