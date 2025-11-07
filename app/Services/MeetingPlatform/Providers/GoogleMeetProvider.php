<?php

namespace App\Services\MeetingPlatform\Providers;

use App\Models\Appointment;
use App\Models\BookingSetting;
use App\Models\CalendarConnection;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\MeetingPlatform\MeetingProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleMeetProvider implements MeetingProviderInterface
{
    protected GoogleCalendarService $calendarService;

    public function __construct()
    {
        $this->calendarService = new GoogleCalendarService();
    }

    /**
     * Create a Google Meet meeting for an appointment
     */
    public function createMeeting(Appointment $appointment, BookingSetting $settings): array
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$connection || !$this->calendarService->isConfigured()) {
            Log::warning('Google Calendar not configured or no active connection, using fallback', [
                'appointment_id' => $appointment->id,
                'has_connection' => $connection !== null,
                'is_configured' => $this->calendarService->isConfigured(),
            ]);
            return $this->getFallbackMeeting();
        }

        $eventData = [
            'summary' => $appointment->title,
            'description' => $appointment->description ?? '',
            'start_time' => $appointment->start_datetime->toRfc3339String(),
            'end_time' => $appointment->end_datetime->toRfc3339String(),
            'timezone' => $appointment->timezone ?? config('app.timezone'),
            'attendees' => $this->getAttendees($appointment),
            'add_meet' => true, // Enable Google Meet
        ];

        $event = $this->calendarService->createEvent($connection, $eventData);

        if (!$event) {
            Log::error('Failed to create Google Calendar event, using fallback', [
                'appointment_id' => $appointment->id,
                'connection_id' => $connection->id,
            ]);
            return $this->getFallbackMeeting();
        }

        // Save the calendar connection relationship
        $appointment->calendar_connection_id = $connection->id;
        $appointment->external_calendar_event_id = $event['id'];
        $appointment->save();

        return [
            'url' => $event['meet_link'] ?? $event['hangout_link'],
            'id' => $event['id'],
            'password' => null, // Google Meet doesn't use passwords
        ];
    }

    /**
     * Update an existing Google Meet meeting
     */
    public function updateMeeting(Appointment $appointment, BookingSetting $settings): array
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$appointment->external_calendar_event_id || !$connection || !$this->calendarService->isConfigured()) {
            return [
                'url' => $appointment->meeting_url,
                'id' => $appointment->meeting_id,
                'password' => null,
            ];
        }

        $eventData = [
            'summary' => $appointment->title,
            'description' => $appointment->description ?? '',
            'start_time' => $appointment->start_datetime->toRfc3339String(),
            'end_time' => $appointment->end_datetime->toRfc3339String(),
            'timezone' => $appointment->timezone ?? config('app.timezone'),
            'attendees' => $this->getAttendees($appointment),
            'add_meet' => true,
        ];

        $updated = $this->calendarService->updateEvent(
            $connection,
            $appointment->external_calendar_event_id,
            $eventData
        );

        if (!$updated) {
            Log::error('Failed to update Google Calendar event', [
                'appointment_id' => $appointment->id,
                'event_id' => $appointment->external_calendar_event_id,
            ]);
        }

        return [
            'url' => $appointment->meeting_url,
            'id' => $appointment->meeting_id,
            'password' => null,
        ];
    }

    /**
     * Delete a Google Meet meeting
     */
    public function deleteMeeting(Appointment $appointment, BookingSetting $settings): bool
    {
        $connection = $this->getActiveConnection($appointment);

        if (!$appointment->external_calendar_event_id || !$connection || !$this->calendarService->isConfigured()) {
            return true;
        }

        return $this->calendarService->deleteEvent($connection, $appointment->external_calendar_event_id);
    }

    /**
     * Check if Google Meet is properly configured
     */
    public function isConfigured(BookingSetting $settings): bool
    {
        if ($settings->meeting_platform !== 'google_meet' || !$settings->google_meet_enabled) {
            return false;
        }

        // Check if service is configured
        if (!$this->calendarService->isConfigured()) {
            return false;
        }

        // Check if user has an active Google Calendar connection
        $userId = $settings->user_id ?? auth()->id();
        if (!$userId) {
            return false;
        }

        return CalendarConnection::where('user_id', $userId)
            ->where('provider', 'google_calendar')
            ->syncEnabled()
            ->exists();
    }

    /**
     * Get the active Google Calendar connection for the appointment's user
     */
    protected function getActiveConnection(Appointment $appointment): ?CalendarConnection
    {
        $userId = $appointment->user_id ?? $appointment->appointmentType?->user_id ?? auth()->id();

        if (!$userId) {
            return null;
        }

        // First try to get the existing connection if appointment has one
        if ($appointment->calendar_connection_id) {
            $connection = CalendarConnection::find($appointment->calendar_connection_id);
            if ($connection && $connection->canSync()) {
                return $connection;
            }
        }

        // Otherwise get the primary or first active Google Calendar connection
        return CalendarConnection::where('user_id', $userId)
            ->where('provider', 'google_calendar')
            ->syncEnabled()
            ->orderBy('is_primary', 'desc')
            ->first();
    }

    /**
     * Get attendees email list for the event
     */
    protected function getAttendees(Appointment $appointment): array
    {
        $attendees = [];

        // Add the client/guest email if available
        if ($appointment->email) {
            $attendees[] = $appointment->email;
        }

        // Add the host/owner email if available
        if ($appointment->user?->email) {
            $attendees[] = $appointment->user->email;
        }

        return array_unique($attendees);
    }

    /**
     * Get fallback meeting details when API fails
     */
    protected function getFallbackMeeting(): array
    {
        $meetingCode = strtolower(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));

        return [
            'url' => "https://meet.google.com/{$meetingCode}",
            'id' => $meetingCode,
            'password' => null,
        ];
    }
}
