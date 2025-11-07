<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8" wire:loading.class="opacity-50">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>

    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-6">
            @if($bookingSetting->logo_url)
                <img src="{{ $bookingSetting->logo_url }}" alt="Logo" class="h-16 mx-auto mb-4">
            @endif
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2" style="color: {{ $bookingSetting->brand_color ?? '#3B82F6' }}">
                {{ $bookingSetting->display_name }}
            </h1>
            @if($bookingSetting->welcome_message)
                <p class="mt-2 text-gray-600 text-base sm:text-lg max-w-2xl mx-auto">{{ $bookingSetting->welcome_message }}</p>
            @endif
        </div>

        {{-- Progress Steps --}}
        @if(!$confirmed)
            <div class="mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <div class="flex items-center justify-between max-w-2xl mx-auto">
                        @foreach(['Select Service', 'Choose Time', 'Your Info'] as $index => $label)
                            <div class="flex items-center {{ $index < 2 ? 'flex-1' : '' }}">
                                <div class="flex items-center">
                                    <div class="
                                        flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm transition-all
                                        {{ $currentStep > ($index + 1) ? 'bg-green-500 text-white' : ($currentStep === ($index + 1) ? 'text-white shadow-lg scale-110' : 'bg-gray-200 text-gray-600') }}
                                    " style="{{ $currentStep === ($index + 1) ? 'background-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') : '' }}">
                                        @if($currentStep > ($index + 1))
                                            ✓
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </div>
                                    <span class="ml-2 text-xs sm:text-sm font-medium {{ $currentStep === ($index + 1) ? 'text-gray-900' : 'text-gray-500' }} hidden sm:inline">
                                        {{ $label }}
                                    </span>
                                </div>
                                @if($index < 2)
                                    <div class="flex-1 h-0.5 mx-2 sm:mx-4 transition-all {{ $currentStep > ($index + 1) ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Content Card --}}
        <div class="bg-white rounded-xl shadow-sm mb-6 overflow-hidden">
            @if(session()->has('error'))
                <div class="m-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="p-6 sm:p-8">
                @if($confirmed)
                    {{-- Step 4: Confirmation --}}
                    <div class="text-center py-12 max-w-2xl mx-auto">
                        {{-- Success Animation --}}
                        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-6 animate-bounce" style="background-color: {{ $bookingSetting->brand_color ?? '#3B82F6' }}15">
                            <svg class="h-12 w-12" style="color: {{ $bookingSetting->brand_color ?? '#3B82F6' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>

                        <h2 class="text-3xl font-bold text-gray-900 mb-3">
                            @if($bookingSetting->require_approval)
                                Request Submitted!
                            @else
                                You're All Set!
                            @endif
                        </h2>

                        <p class="text-gray-600 text-lg mb-8 max-w-lg mx-auto">
                            @if($bookingSetting->require_approval)
                                Your booking request has been submitted and is pending approval. You'll receive a confirmation email once it's approved.
                            @else
                                Your appointment has been confirmed. A confirmation email has been sent to <strong>{{ $attendeeEmail }}</strong>
                            @endif
                        </p>

                        {{-- Appointment Details Card --}}
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-8 text-left shadow-inner border border-gray-200">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: {{ $bookingSetting->brand_color ?? '#3B82F6' }}">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Appointment Details</h3>
                            </div>

                            <dl class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <dt class="text-gray-500 font-medium min-w-[100px]">Service</dt>
                                    <dd class="text-gray-900 font-semibold flex-1">{{ $selectedType->name }}</dd>
                                </div>
                                <div class="h-px bg-gray-300"></div>
                                <div class="flex items-start gap-4">
                                    <dt class="text-gray-500 font-medium min-w-[100px]">Date</dt>
                                    <dd class="text-gray-900 font-semibold flex-1">
                                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                                    </dd>
                                </div>
                                <div class="h-px bg-gray-300"></div>
                                <div class="flex items-start gap-4">
                                    <dt class="text-gray-500 font-medium min-w-[100px]">Time</dt>
                                    <dd class="text-gray-900 font-semibold flex-1">
                                        {{ \Carbon\Carbon::parse($selectedTime, 'H:i')->format('g:i A') }}
                                    </dd>
                                </div>
                                <div class="h-px bg-gray-300"></div>
                                <div class="flex items-start gap-4">
                                    <dt class="text-gray-500 font-medium min-w-[100px]">Duration</dt>
                                    <dd class="text-gray-900 font-semibold flex-1">{{ $selectedType->duration_minutes }} minutes</dd>
                                </div>
                                <div class="h-px bg-gray-300"></div>
                                <div class="flex items-start gap-4">
                                    <dt class="text-gray-500 font-medium min-w-[100px]">Attendee</dt>
                                    <dd class="text-gray-900 font-semibold flex-1">
                                        {{ $attendeeName }}<br>
                                        <span class="text-sm font-normal text-gray-600">{{ $attendeeEmail }}</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                            <a
                                href="{{ url('/') }}"
                                class="px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-all"
                            >
                                Return Home
                            </a>
                        </div>

                        {{-- Additional Info --}}
                        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>What's next?</strong> Check your email for confirmation details. If you need to make changes, please contact us directly.
                            </p>
                        </div>
                    </div>

                @elseif($currentStep === 1)
                    {{-- Step 1: Select Appointment Type --}}
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Select a Service</h2>

                    <div class="space-y-3">
                        @forelse($appointmentTypes as $type)
                            <button
                                wire:click="selectType({{ $type->id }})"
                                class="w-full text-left p-5 border-2 rounded-xl hover:shadow-md transition-all duration-200 {{ $selectedTypeId === $type->id ? 'ring-2' : 'border-gray-200' }}"
                                style="{{ $selectedTypeId === $type->id ? 'border-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') . '; box-shadow: 0 0 0 1px ' . ($bookingSetting->brand_color ?? '#3B82F6') : '' }}"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $type->color }}"></div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $type->name }}</h3>
                                        </div>

                                        @if($type->description)
                                            <p class="text-gray-600 mb-3 text-sm">{{ $type->description }}</p>
                                        @endif

                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ $type->duration_minutes }} min
                                            </span>
                                            @if($type->price > 0)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                    </svg>
                                                    ${{ number_format($type->price, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <svg class="w-6 h-6 text-gray-400 flex-shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>
                        @empty
                            <div class="text-center py-12 text-gray-500">
                                <p>No services available for booking at this time.</p>
                            </div>
                        @endforelse
                    </div>

                @elseif($currentStep === 2)
                    {{-- Step 2: Select Date and Time --}}
                    <div class="mb-6">
                        <button
                            wire:click="goBack"
                            class="text-gray-600 hover:text-gray-900 flex items-center gap-1 text-sm transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Choose Date & Time</h2>
                    <p class="text-gray-600 mb-6">{{ $selectedType->name }} - {{ $selectedType->duration_minutes }} minutes</p>

                    <div class="grid md:grid-cols-2 gap-8">
                        {{-- Calendar --}}
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Select Date
                            </h3>

                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                {{-- Available Dates List --}}
                                <div class="space-y-2 max-h-[400px] overflow-y-auto custom-scrollbar">
                                    @forelse($availableDates as $dateInfo)
                                        <button
                                            wire:click="selectDate('{{ $dateInfo['date'] }}')"
                                            type="button"
                                            class="w-full text-left px-4 py-3 border-2 rounded-lg transition-all duration-200 hover:shadow-sm
                                            {{ $selectedDate === $dateInfo['date'] ? 'ring-2 text-white font-semibold' : 'border-gray-200 hover:border-gray-300' }}"
                                            style="{{ $selectedDate === $dateInfo['date'] ? 'background-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') . '; border-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') : '' }}"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium {{ $selectedDate === $dateInfo['date'] ? 'text-white' : 'text-gray-900' }}">
                                                        {{ $dateInfo['day_name'] }}
                                                    </div>
                                                    <div class="text-sm {{ $selectedDate === $dateInfo['date'] ? 'text-white opacity-90' : 'text-gray-600' }}">
                                                        {{ \Carbon\Carbon::parse($dateInfo['date'])->format('F j, Y') }}
                                                    </div>
                                                </div>
                                                @if($selectedDate === $dateInfo['date'])
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        </button>
                                    @empty
                                        <div class="text-center py-12 text-gray-400">
                                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium">No dates available</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Available Time Slots --}}
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                @if($selectedDate)
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, M j, Y') }}
                                @else
                                    Select Time
                                @endif
                            </h3>

                            @if($selectedDate)
                                <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                    @forelse($availableSlots as $slotInfo)
                                        <button
                                            wire:click="selectTime('{{ $slotInfo['time'] }}')"
                                            type="button"
                                            class="w-full text-center px-4 py-3 border-2 rounded-lg hover:shadow-sm transition-all duration-200 font-medium
                                            {{ $selectedTime === $slotInfo['time'] ? 'ring-2 text-white' : 'border-gray-200 text-gray-700 hover:border-gray-300' }}"
                                            style="{{ $selectedTime === $slotInfo['time'] ? 'background-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') . '; border-color: ' . ($bookingSetting->brand_color ?? '#3B82F6') : '' }}"
                                        >
                                            {{ $slotInfo['formatted'] }}
                                        </button>
                                    @empty
                                        <div class="text-center py-12 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="text-sm">No times available for this date</p>
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <div class="text-center py-16 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
                                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-sm font-medium">Please select a date first</p>
                                </div>
                            @endif
                        </div>
                    </div>


                @elseif($currentStep === 3)
                    {{-- Step 3: Contact Information --}}
                    <div class="mb-6">
                        <button
                            wire:click="goBack"
                            class="text-gray-600 hover:text-gray-900 flex items-center gap-1 text-sm transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Your Information</h2>
                    <p class="text-gray-600 mb-6">
                        {{ \Carbon\Carbon::parse($selectedDate . ' ' . $selectedTime)->format('F j, Y \a\t g:i A') }}
                    </p>

                    <form wire:submit.prevent="submitBooking" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="attendeeName"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                placeholder="John Doe"
                                required
                            >
                            @error('attendeeName') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="email"
                                wire:model="attendeeEmail"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                placeholder="john@example.com"
                                required
                            >
                            @error('attendeeEmail') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number @if($selectedType->require_phone)<span class="text-red-500">*</span>@endif
                            </label>
                            <input
                                type="tel"
                                wire:model="attendeePhone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                placeholder="+1 (555) 123-4567"
                                {{ $selectedType->require_phone ? 'required' : '' }}
                            >
                            @error('attendeePhone') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if($selectedType->require_company)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Company <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="attendeeCompany"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                    placeholder="Acme Inc."
                                    required
                                >
                                @error('attendeeCompany') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea
                                wire:model="notes"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none"
                                placeholder="Anything we should know?"
                            ></textarea>
                            @error('notes') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                class="w-full px-6 py-4 text-white font-semibold rounded-lg hover:opacity-90 transition-all shadow-md hover:shadow-lg transform hover:scale-[1.02]"
                                style="background-color: {{ $bookingSetting->brand_color ?? '#3B82F6' }}"
                            >
                                Confirm Booking
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center text-sm text-gray-500">
            <p>Powered by {{ config('app.name') }}</p>
        </div>
    </div>

    {{-- Loading Spinner - Inline --}}
    <div wire:loading class="fixed top-4 right-4 z-50">
        <div class="bg-white rounded-lg px-4 py-3 shadow-lg flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700">Loading...</span>
        </div>
    </div>
</div>
