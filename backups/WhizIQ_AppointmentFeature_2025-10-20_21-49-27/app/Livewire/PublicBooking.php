<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\BookingSetting;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;

class PublicBooking extends Component
{
    public $bookingSlug;
    public $bookingSetting;
    public $currentStep = 1;

    // Step 1: Select appointment type
    public $selectedTypeId;
    public $selectedType;

    // Step 2: Select date and time
    public $selectedDate;
    public $selectedTime;
    public $availableDates = [];
    public $availableSlots = [];

    // Step 3: Contact information
    public $attendeeName;
    public $attendeeEmail;
    public $attendeePhone;
    public $attendeeCompany;
    public $notes;

    // Confirmation
    public $confirmationToken;
    public $confirmed = false;

    public function mount($slug)
    {
        $this->bookingSlug = $slug;

        $this->bookingSetting = BookingSetting::where('booking_slug', $slug)
            ->where('is_booking_enabled', true)
            ->first();

        if (!$this->bookingSetting) {
            abort(404, 'Booking page not found or is currently disabled.');
        }
    }

    public function selectType($typeId)
    {
        $this->selectedTypeId = $typeId;
        $this->selectedType = AppointmentType::findOrFail($typeId);

        // Load available dates for the next 30 days
        $service = new AvailabilityService();
        $this->availableDates = $service->getAvailableDates(
            $this->bookingSetting->user_id,
            $this->bookingSetting->max_booking_days_ahead ?? 30
        );

        $this->currentStep = 2;
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;

        // Load available time slots for selected date
        $service = new AvailabilityService();
        $minNoticeHours = $this->bookingSetting->min_booking_notice_hours ?? 0;

        $this->availableSlots = $service->getAvailableSlots(
            $this->bookingSetting->user_id,
            Carbon::parse($date),
            $this->selectedType->getTotalDurationMinutesAttribute(),
            $minNoticeHours
        );
    }

    public function selectTime($time)
    {
        $this->selectedTime = $time;
        $this->currentStep = 3;
    }

    public function goBack()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;

            if ($this->currentStep === 2) {
                $this->selectedTime = null;
            } elseif ($this->currentStep === 1) {
                $this->selectedDate = null;
                $this->selectedTime = null;
                $this->availableSlots = [];
            }
        }
    }

    public function submitBooking()
    {
        $this->validate([
            'attendeeName' => 'required|string|max:255',
            'attendeeEmail' => 'required|email|max:255',
            'attendeePhone' => $this->selectedType->require_phone ? 'required|string|max:20' : 'nullable|string|max:20',
            'attendeeCompany' => $this->selectedType->require_company ? 'required|string|max:255' : 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $startDateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);

        // Check if slot is still available
        $service = new AvailabilityService();
        $endDateTime = $startDateTime->copy()->addMinutes($this->selectedType->getTotalDurationMinutesAttribute());

        if ($service->isSlotBooked($this->bookingSetting->user_id, $startDateTime, $endDateTime)) {
            session()->flash('error', 'This time slot is no longer available. Please select another time.');
            $this->currentStep = 2;
            $this->selectDate($this->selectedDate);
            return;
        }

        // Create the appointment
        $this->confirmationToken = Str::random(32);

        $appointment = Appointment::create([
            'user_id' => $this->bookingSetting->user_id,
            'appointment_type_id' => $this->selectedTypeId,
            'title' => $this->selectedType->name . ' with ' . $this->attendeeName,
            'description' => $this->selectedType->description,
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'timezone' => $this->bookingSetting->timezone,
            'status' => $this->bookingSetting->require_approval ? 'scheduled' : 'confirmed',
            'attendee_name' => $this->attendeeName,
            'attendee_email' => $this->attendeeEmail,
            'attendee_phone' => $this->attendeePhone,
            'attendee_company' => $this->attendeeCompany,
            'notes' => $this->notes,
            'confirmation_token' => $this->confirmationToken,
            'booked_via' => 'public_form',
        ]);

        $this->confirmed = true;
        $this->currentStep = 4;

        // Send confirmation email to attendee
        \Illuminate\Support\Facades\Notification::route('mail', $this->attendeeEmail)
            ->notify(new \App\Notifications\AppointmentConfirmedNotification($appointment));

        // Send notification to appointment owner
        $owner = \App\Models\User::find($this->bookingSetting->user_id);
        if ($owner) {
            $owner->notify(new \App\Notifications\NewAppointmentBookedNotification($appointment));
        }
    }

    public function render()
    {
        $appointmentTypes = AppointmentType::where('user_id', $this->bookingSetting->user_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.public-booking', [
            'appointmentTypes' => $appointmentTypes,
        ])->layout('components.layouts.app');
    }
}
