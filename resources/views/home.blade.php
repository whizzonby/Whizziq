<x-layouts.app>
    <x-slot name="title">
        {{ __('WhizzIQ - Intelligent Business Management Platform') }}
    </x-slot>

    {{-- Hero Section --}}
    <x-section.hero class="w-full mb-8 md:mb-72">
        <div class="mx-auto text-center h-160 md:h-180 px-4">
            <x-pill class="text-primary-500 bg-primary-50">{{ __('All-in-One Business Platform') }}</x-pill>
            <x-heading.h1 class="mt-4 text-primary-50 font-bold">
                {{ __('Intelligent Business Management') }}
                <br class="hidden sm:block">
                {{ __('Powered by AI') }}
            </x-heading.h1>

            <p class="text-primary-50 m-3">{{ __('Manage Finances, Clients, Tasks, Documents, and Appointments in One Powerful Platform') }}</p>

            <div class="flex flex-wrap gap-4 justify-center flex-col md:flex-row mt-6">
                <x-effect.glow></x-effect.glow>

                <x-button-link.secondary href="#pricing" class="self-center py-3!" elementType="a">
                    {{ __('Get Started') }}
                </x-button-link.secondary>
                <x-button-link.primary-outline href="#features" class=" bg-transparent self-center py-3! text-white border-white">
                    {{ __('Learn More') }}
                </x-button-link.primary-outline>
            </div>

            <x-user-ratings link="#testimonials" class="items-center justify-center mt-6 relative z-40">
                <x-slot name="avatars">
                    <x-user-ratings.avatar src="https://unsplash.com/photos/rDEOVtE7vOs/download?ixid=M3wxMjA3fDB8MXxzZWFyY2h8Mnx8cGVyc29ufGVufDB8fHx8MTcxMzY4NDI1MHww&force=true&w=640" alt="testimonial 1"/>
                    <x-user-ratings.avatar src="https://unsplash.com/photos/c_GmwfHBDzk/download?ixid=M3wxMjA3fDB8MXxzZWFyY2h8M3x8cGVyc29ufGVufDB8fHx8MTcxMzY4NDI1MHww&force=true&w=640" alt="testimonial 2"/>
                    <x-user-ratings.avatar src="https://unsplash.com/photos/QXevDflbl8A/download?ixid=M3wxMjA3fDB8MXxzZWFyY2h8NHx8cGVyc29ufGVufDB8fHx8MTcxMzY4NDI1MHww&force=true&w=640" alt="testimonial 3"/>
                    <x-user-ratings.avatar src="https://unsplash.com/photos/mjRwhvqEC0U/download?ixid=M3wxMjA3fDB8MXxzZWFyY2h8Nnx8cGVyc29ufGVufDB8fHx8MTcxMzY4NDI1MHww&force=true&w=640" alt="testimonial 4"/>
                    <x-user-ratings.avatar src="https://unsplash.com/photos/C8Ta0gwPbQg/download?ixid=M3wxMjA3fDB8MXxzZWFyY2h8MTl8fHBlcnNvbnxlbnwwfHx8fDE3MTM2ODQyNTB8MA&force=true&w=640" alt="testimonial 5"/>
                </x-slot>

                {{ __('Join thousands of business owners managing their operations with WhizzIQ.') }}
            </x-user-ratings>

            <div class="mx-auto md:max-w-3xl lg:max-w-5xl">
                <img class="drop-shadow-2xl mt-8 transition hover:scale-101 rounded-2xl" src="{{URL::asset('/images/features/hero-image.png')}}" />
            </div>
        </div>
    </x-section.hero>

    {{-- Core Features Section --}}
    <div class="text-center mt-16 px-4" id="features">
        <x-heading.h6 class="text-primary-500">
            {{ __('Everything You Need') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Powerful Features for Your Business') }}
        </x-heading.h2>
        <p class="mt-4 max-w-2xl mx-auto text-gray-600">
            {{ __('WhizzIQ combines essential business tools with AI-powered automation to help you work smarter, not harder.') }}
        </p>
    </div>

    {{-- Feature Grid --}}
    <x-section.columns class="max-w-none md:max-w-6xl mt-12">
        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500">
                    {{ __('Complete Financial Control') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Smart Financial Management') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('Track expenses with AI-powered auto-categorization, manage invoices and payments, monitor cash flow in real-time, and stay compliant with automated tax calculations and filing.') }}
            </p>
        </x-section.column>

        <x-section.column>
            <img src="{{URL::asset('/images/features/payments.png')}}" dir="right" ></img>
        </x-section.column>
    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl flex-wrap-reverse">
        <x-section.column >
            <img src="{{URL::asset('/images/features/colors.png')}}" />
        </x-section.column>

        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500">
                    {{ __('CRM & Sales Pipeline') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Manage Clients & Close Deals') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('Track contacts, deals, and interactions in one place. Visualize your sales pipeline with drag-and-drop deal stages. Set automated follow-up reminders and send email campaigns.') }}
            </p>
        </x-section.column>
    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6" >
        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500">
                    {{ __('Intelligent Productivity') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('AI-Powered Task Management') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('AI extracts action items from meeting notes automatically. Visualize tasks on a kanban board, set OKR-style goals with key results, and get smart priority recommendations.') }}
            </p>
        </x-section.column>

        <x-section.column>
            <img src="{{URL::asset('/images/features/plans.png')}}" class="rounded-2xl"/>
        </x-section.column>
    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6 flex-wrap-reverse">
        <x-section.column >
            <img src="{{URL::asset('/images/features/checkout.png')}}" class="rounded-2xl" />
        </x-section.column>

        <x-section.column>
            <div x-intersect="$el.classList.add('slide-in-top')">
                <x-heading.h6 class="text-primary-500">
                    {{ __('Enterprise Document Management') }}
                </x-heading.h6>
                <x-heading.h2 class="text-primary-900">
                    {{ __('Secure Document Vault with AI') }}
                </x-heading.h2>
            </div>

            <p class="mt-4">
                {{ __('Store, organize, and analyze documents with enterprise-grade security. AI-powered analysis extracts key information, detects duplicates, and enables full-text search across all your files.') }}
            </p>
        </x-section.column>
    </x-section.columns>

    {{-- Additional Features Grid --}}
    <div class="text-center mt-16" x-intersect="$el.classList.add('slide-in-top')">
        <x-heading.h6 class="text-primary-500">
            {{ __('And Much More') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Complete Business Suite') }}
        </x-heading.h2>
    </div>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="users" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Appointment Booking') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Public booking pages, calendar sync, and automatic Zoom/Meet links for seamless scheduling.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="translatable" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Tax Compliance') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Automated tax calculations, quarterly reports, and deadline reminders powered by AI.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="seo" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Business Analytics') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Automated SWOT analysis, risk assessments, and strategic recommendations.') }}</p>
        </x-section.column>
    </x-section.columns>

    <x-section.columns class="max-w-none md:max-w-6xl mt-6">
        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="user-dashboard" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Marketing Tools') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Social media management, email campaigns, and engagement tracking across all platforms.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="tool" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Password Vault') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Encrypted storage for credentials and sensitive data with audit trails.') }}</p>
        </x-section.column>

        <x-section.column class="flex flex-col items-center justify-center text-center">
            <x-icon.fancy name="development" class="w-2/5 mx-auto" />
            <x-heading.h3 class="mx-auto pt-2">
                {{ __('Real-time Sync') }}
            </x-heading.h3>
            <p class="mt-2">{{ __('Automatic synchronization with Google, Outlook, and social platforms.') }}</p>
        </x-section.column>
    </x-section.columns>

    {{-- Pricing Section --}}
    <div class="mx-4 mt-16">
        <x-heading.h6 class="text-center mt-20 text-primary-500" id="pricing">
            {{ __('Start Managing Smarter Today') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900 text-center">
            {{ __('Choose Your Plan') }}
        </x-heading.h2>
    </div>

    <div class="pricing">
        <x-plans.all calculate-saving-rates="true" show-default-product="1"/>
        <x-products.all />
    </div>

    {{-- FAQ Section --}}
    <div class="text-center mt-24 mx-4" id="faq">
        <x-heading.h6 class="text-primary-500">
            {{ __('FAQ') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Got Questions?') }}
        </x-heading.h2>
        <p>{{ __('Here are the most common questions to help you with your decision.') }}</p>
    </div>

    <div class="max-w-none md:max-w-6xl mx-auto">
        <x-accordion class="mt-4 p-8">
            <x-accordion.item active="true" name="faqs">
                <x-slot name="title">{{ __('What is WhizzIQ?') }}</x-slot>

                <p>
                    {{ __('WhizzIQ is an all-in-one intelligent business management platform that helps you manage finances, clients, tasks, documents, appointments, and strategic planning from a unified dashboard. Powered by AI, it automates routine tasks and provides actionable insights.') }}
                </p>
            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('What features does WhizzIQ offer?') }}</x-slot>

                <p class="mt-4">
                    {{ __('WhizzIQ includes:') }}
                </p>

                <ul class="mt-4 list-disc ms-4 ps-4">
                    <li>{{ __('Financial Management: Track expenses with AI auto-categorization, manage invoices and payments, monitor cash flow') }}</li>
                    <li>{{ __('Tax Compliance: Automated tax calculations, quarterly reports, deadline reminders') }}</li>
                    <li>{{ __('Document Vault: Enterprise-grade document management with AI analysis and version control') }}</li>
                    <li>{{ __('CRM & Sales: Manage contacts, visualize deal pipeline, track interactions') }}</li>
                    <li>{{ __('Task Management: AI extracts tasks from notes, kanban board, OKR-style goals') }}</li>
                    <li>{{ __('Appointment Booking: Public booking pages, calendar sync, automatic meeting links') }}</li>
                    <li>{{ __('Business Analytics: Automated SWOT analysis, risk assessments, productivity tracking') }}</li>
                    <li>{{ __('Marketing Management: Social media and email campaign tools with AI insights') }}</li>
                </ul>
            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('What integrations does WhizzIQ support?') }}</x-slot>

                <p>
                    {{ __('WhizzIQ integrates with Google Calendar, Outlook, Apple Calendar, Zoom, Google Meet, Facebook, Instagram, LinkedIn, Twitter, and uses OpenAI for intelligent automation.') }}
                </p>
            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('Who is WhizzIQ for?') }}</x-slot>

                <p>
                    {{__('WhizzIQ is perfect for small to medium-sized businesses, consultants, freelancers, agencies, and entrepreneurs who need to manage multiple aspects of their business from one platform.')}}
                </p>
            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{'What technology is WhizzIQ built on?'}}</x-slot>

                <p>
                    {{ __('WhizzIQ is built on') }} <a href="https://laravel.com" target="_blank">Laravel 12</a> {{ __(', with') }} <a target="_blank" href="https://filamentphp.com/">Filament 4</a> {{ __(' for the admin interface. It uses TailwindCSS, Livewire, AlpineJS, and OpenAI for intelligent automation.')}}
                </p>
            </x-accordion.item>

            <x-accordion.item active="false" name="faqs">
                <x-slot name="title">{{ __('Do you offer support?') }}</x-slot>

                <p>
                    {{ __('Yes! We offer email and support to help you with any issues. Write us at') }} <a href="mailto:{{config('app.support_email')}}" class="text-primary-500 hover:underline">{{config('app.support_email')}}</a>
                </p>
            </x-accordion.item>
        </x-accordion>
    </div>

</x-layouts.app>
