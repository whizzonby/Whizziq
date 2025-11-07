<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\ContactSyncService;
use App\Services\CalendarSyncService;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    public function __construct(
        protected ContactSyncService $contactSyncService,
        protected CalendarSyncService $calendarSyncService
    ) {}

    /**
     * Handle the Appointment "created" event.
     * Auto-sync appointment to contacts and Google Calendar
     */
    public function created(Appointment $appointment): void
    {
        // Auto-sync to contacts (runs in background to avoid slowing down booking)
        $this->contactSyncService->syncAppointmentToContact($appointment);

        // Auto-sync to Google Calendar if appointment is confirmed/scheduled
        if (in_array($appointment->status, ['confirmed', 'scheduled'])) {
            $this->syncToCalendar($appointment);
        }
    }

    /**
     * Handle the Appointment "updated" event.
     * Sync if attendee info changed or appointment details changed
     */
    public function updated(Appointment $appointment): void
    {
        // If attendee email changed or contact not linked, try to sync again
        if ($appointment->wasChanged(['attendee_email', 'attendee_name', 'attendee_phone', 'attendee_company'])) {
            $this->contactSyncService->syncAppointmentToContact($appointment);
        }

        // Sync to Google Calendar if relevant fields changed
        if ($appointment->wasChanged(['title', 'description', 'start_datetime', 'end_datetime', 'location', 'venue_id', 'room_name', 'meeting_url', 'status'])) {
            // If status changed to cancelled, delete from calendar
            if ($appointment->status === 'cancelled' && $appointment->calendar_event_id) {
                $this->calendarSyncService->deleteAppointmentFromCalendar($appointment);
            }
            // If status is confirmed/scheduled, sync to calendar
            elseif (in_array($appointment->status, ['confirmed', 'scheduled'])) {
                $this->syncToCalendar($appointment);
            }
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        // Delete from Google Calendar
        if ($appointment->calendar_event_id) {
            $this->calendarSyncService->deleteAppointmentFromCalendar($appointment);
        }

        // Optional: You could log this as a cancellation interaction
        // For now, we keep the contact but unlink the appointment
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        // Re-sync if appointment is restored
        $this->contactSyncService->syncAppointmentToContact($appointment);

        // Re-sync to calendar if status is confirmed/scheduled
        if (in_array($appointment->status, ['confirmed', 'scheduled'])) {
            $this->syncToCalendar($appointment);
        }
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        // Delete from Google Calendar
        if ($appointment->calendar_event_id) {
            $this->calendarSyncService->deleteAppointmentFromCalendar($appointment);
        }

        // Permanent deletion - no action needed
    }

    /**
     * Sync appointment to Google Calendar
     */
    protected function syncToCalendar(Appointment $appointment): void
    {
        try {
            $result = $this->calendarSyncService->pushAppointmentToCalendar($appointment);

            if ($result['success']) {
                Log::info('Appointment synced to calendar via observer', [
                    'appointment_id' => $appointment->id,
                    'event_id' => $result['event_id'] ?? null,
                ]);
            } else {
                Log::warning('Failed to sync appointment to calendar', [
                    'appointment_id' => $appointment->id,
                    'message' => $result['message'],
                    'conflicts' => $result['conflicts'] ?? [],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing appointment to calendar in observer', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
