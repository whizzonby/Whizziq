<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\BookingSetting;
use App\Models\User;
use App\Services\MeetingPlatform\MeetingPlatformService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\NewAppointmentBookedNotification;
use Illuminate\Support\Facades\Log;

class ProcessNewAppointment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Appointment $appointment,
        public BookingSetting $bookingSetting
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Create meeting link if platform is configured (slow operation)
            $meetingService = new MeetingPlatformService();
            $this->appointment = $meetingService->createMeetingForAppointment(
                $this->appointment,
                $this->bookingSetting
            );

            // Send confirmation email to attendee (slow operation)
            if ($this->appointment->attendee_email) {
                Notification::route('mail', $this->appointment->attendee_email)
                    ->notify(new AppointmentConfirmedNotification($this->appointment));
            }

            // Send notification to appointment owner (slow operation)
            $owner = User::find($this->bookingSetting->user_id);
            if ($owner) {
                $owner->notify(new NewAppointmentBookedNotification($this->appointment));
            }

            Log::info('Appointment processed successfully', [
                'appointment_id' => $this->appointment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process appointment', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the job - appointment is already created
            // Just log the error so admin can follow up
        }
    }
}
